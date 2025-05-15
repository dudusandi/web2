<?php
// Verifica se a sessão já está ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../dao/produto_dao.php';

// Inicializa o carrinho se não existir
if (!isset($_SESSION['carrinhos'])) {
    $_SESSION['carrinhos'] = [];
}

// Função para obter o carrinho do cliente atual
function obterCarrinhoAtual() {
    if (!isset($_SESSION['usuario_id'])) {
        error_log("Usuário não está logado");
        return [];
    }
    
    $clienteId = $_SESSION['usuario_id'];
    
    // Inicializa a estrutura de carrinhos se não existir
    if (!isset($_SESSION['carrinhos'])) {
        $_SESSION['carrinhos'] = [];
    }
    
    // Inicializa o carrinho do cliente se não existir
    if (!isset($_SESSION['carrinhos'][$clienteId])) {
        $_SESSION['carrinhos'][$clienteId] = [];
        error_log("Criando novo carrinho para cliente ID: " . $clienteId);
    } else {
        error_log("Usando carrinho existente para cliente ID: " . $clienteId);
    }
    
    return $_SESSION['carrinhos'][$clienteId];
}

// Função para adicionar produto ao carrinho
function adicionarAoCarrinho($produtoId, $quantidade = 1) {
    if (!isset($_SESSION['usuario_id'])) {
        error_log("Tentativa de adicionar produto sem usuário logado");
        return;
    }
    
    $clienteId = $_SESSION['usuario_id'];
    error_log("Adicionando produto ao carrinho do cliente ID: " . $clienteId);
    $carrinho = obterCarrinhoAtual();
    
    if (isset($carrinho[$produtoId])) {
        $carrinho[$produtoId] += $quantidade;
        error_log("Produto já existe no carrinho. Nova quantidade: " . $carrinho[$produtoId]);
    } else {
        $carrinho[$produtoId] = $quantidade;
        error_log("Novo produto adicionado ao carrinho. Quantidade: " . $quantidade);
    }
    
    $_SESSION['carrinhos'][$clienteId] = $carrinho;
    error_log("Estado final do carrinho: " . print_r($carrinho, true));
}

// Função para remover produto do carrinho
function removerDoCarrinho($produtoId) {
    if (!isset($_SESSION['usuario_id'])) {
        return;
    }
    
    $clienteId = $_SESSION['usuario_id'];
    $carrinho = obterCarrinhoAtual();
    
    if (isset($carrinho[$produtoId])) {
        unset($carrinho[$produtoId]);
        $_SESSION['carrinhos'][$clienteId] = $carrinho;
    }
}

// Função para atualizar quantidade
function atualizarQuantidade($produtoId, $quantidade) {
    if (!isset($_SESSION['usuario_id'])) {
        return;
    }
    
    $clienteId = $_SESSION['usuario_id'];
    $carrinho = obterCarrinhoAtual();
    
    if ($quantidade > 0) {
        $carrinho[$produtoId] = $quantidade;
    } else {
        unset($carrinho[$produtoId]);
    }
    
    $_SESSION['carrinhos'][$clienteId] = $carrinho;
}

// Função para limpar carrinho
function limparCarrinho() {
    if (!isset($_SESSION['usuario_id'])) {
        return;
    }
    
    $clienteId = $_SESSION['usuario_id'];
    $_SESSION['carrinhos'][$clienteId] = [];
}

// Função para obter produtos do carrinho
function obterProdutosCarrinho() {
    if (!isset($_SESSION['usuario_id'])) {
        error_log("Usuário não está logado ao tentar obter produtos do carrinho");
        return [];
    }

    try {
        $pdo = Database::getConnection();
        $produtoDao = new ProdutoDAO($pdo);
        
        $produtos = [];
        $carrinho = obterCarrinhoAtual();
        
        error_log("Carrinho atual: " . print_r($carrinho, true));
        
        foreach ($carrinho as $produtoId => $quantidade) {
            $produto = $produtoDao->buscarPorId($produtoId);
            if ($produto) {
                $produtos[] = [
                    'id' => $produto->getId(),
                    'nome' => $produto->getNome(),
                    'preco' => $produto->getPreco(),
                    'quantidade' => $quantidade,
                    'subtotal' => $produto->getPreco() * $quantidade
                ];
            }
        }
        
        error_log("Produtos obtidos: " . print_r($produtos, true));
        return $produtos;
    } catch (Exception $e) {
        error_log("Erro ao obter produtos do carrinho: " . $e->getMessage());
        return [];
    }
}

// Função para calcular total do carrinho
function calcularTotalCarrinho() {
    $produtos = obterProdutosCarrinho();
    $total = 0;
    foreach ($produtos as $produto) {
        $total += $produto['subtotal'];
    }
    return $total;
}

// Processar ações do carrinho
if (!defined('CARRINHO_LOGIC_ONLY')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verifica se o usuário está logado
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ../view/login.php');
            exit;
        }

        $acao = $_POST['acao'] ?? '';
        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 1);

        switch ($acao) {
            case 'adicionar':
                adicionarAoCarrinho($produtoId, $quantidade);
                break;
            case 'remover':
                removerDoCarrinho($produtoId);
                break;
            case 'atualizar':
                atualizarQuantidade($produtoId, $quantidade);
                break;
            case 'limpar':
                limparCarrinho();
                break;
        }

        // Redirecionar de volta para a página anterior ou para o carrinho
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'carrinho.php';
        header('Location: ' . $redirect);
        exit;
    }

    // Retornar dados do carrinho em JSON
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['json'])) {
        // Verifica se o usuário está logado
        if (!isset($_SESSION['usuario_id'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'erro' => 'Usuário não está logado',
                'produtos' => [],
                'total' => 0
            ]);
            exit;
        }

        try {
            $produtos = obterProdutosCarrinho();
            $total = calcularTotalCarrinho();
            
            error_log("Produtos do carrinho (JSON): " . print_r($produtos, true));
            error_log("Total do carrinho (JSON): " . $total);
            error_log("ID do usuário: " . $_SESSION['usuario_id']);
            error_log("Carrinho atual: " . print_r($_SESSION['carrinhos'][$_SESSION['usuario_id']], true));
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'produtos' => $produtos,
                'total' => $total
            ]);
        } catch (Exception $e) {
            error_log("Erro ao obter carrinho: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'erro' => 'Erro ao obter carrinho: ' . $e->getMessage(),
                'produtos' => [],
                'total' => 0
            ]);
        }
        exit;
    }
}
