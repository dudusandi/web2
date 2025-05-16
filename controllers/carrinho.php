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
    }
    
    return $_SESSION['carrinhos'][$clienteId];
}

// Função para adicionar produto ao carrinho
function adicionarAoCarrinho($produtoId, $quantidade = 1) {
    if (!isset($_SESSION['usuario_id'])) {
        error_log("Tentativa de adicionar produto sem usuário logado");
        return ['success' => false, 'erro' => 'Usuário não logado'];
    }

    $clienteId = $_SESSION['usuario_id'];
    error_log("Adicionando produto ao carrinho do cliente ID: " . $clienteId . " Produto ID: " . $produtoId . " Quantidade: " . $quantidade);

    try {
        $pdo = Database::getConnection();
        $produtoDao = new ProdutoDAO($pdo);
        $produto = $produtoDao->buscarPorId($produtoId);

        if (!$produto) {
            return ['success' => false, 'erro' => 'Produto não encontrado'];
        }

        $estoqueDisponivel = $produto->getQuantidade(); // Assumindo que existe um método getQuantidade() no modelo Produto

        $carrinho = obterCarrinhoAtual();
        $quantidadeJaNoCarrinho = isset($carrinho[$produtoId]) ? (int)$carrinho[$produtoId] : 0;
        $quantidadeTotalDesejada = $quantidadeJaNoCarrinho + $quantidade;
        
        // Se a intenção é apenas atualizar a quantidade para um valor específico, não somar.
        // Isso precisaria de um parâmetro extra ou uma lógica diferente se 'adicionar' também puder significar 'definir quantidade'.
        // Por ora, vamos assumir que 'adicionar' sempre soma à quantidade existente ou define se não existir.

        if ($quantidade <= 0) {
             return ['success' => false, 'erro' => 'Quantidade inválida.'];
        }

        // Se o produto já está no carrinho, a verificação de estoque deve considerar a quantidade adicional.
        // Se o produto não está no carrinho, a quantidade desejada é simplesmente $quantidade.
        $quantidadeParaVerificar = $quantidade;
        if (isset($carrinho[$produtoId])) { // Produto já no carrinho, estamos adicionando mais
             if (($carrinho[$produtoId] + $quantidade) > $estoqueDisponivel) {
                 return ['success' => false, 'erro' => 'Estoque insuficiente. Quantidade disponível: ' . $estoqueDisponivel . '. Você já tem ' . $carrinho[$produtoId] . ' no carrinho.'];
             }
        } else { // Novo produto no carrinho
            if ($quantidade > $estoqueDisponivel) {
                return ['success' => false, 'erro' => 'Estoque insuficiente. Quantidade disponível: ' . $estoqueDisponivel];
            }
        }
        
        if (isset($carrinho[$produtoId])) {
            $carrinho[$produtoId] += $quantidade;
            error_log("Produto já existe no carrinho. Nova quantidade: " . $carrinho[$produtoId]);
        } else {
            $carrinho[$produtoId] = $quantidade;
            error_log("Novo produto adicionado ao carrinho. Quantidade: " . $quantidade);
        }

        $_SESSION['carrinhos'][$clienteId] = $carrinho;
        error_log("Estado final do carrinho: " . print_r($carrinho, true));
        return ['success' => true, 'mensagem' => 'Produto adicionado com sucesso!'];

    } catch (Exception $e) {
        error_log("Erro ao adicionar produto ao carrinho: " . $e->getMessage());
        return ['success' => false, 'erro' => 'Erro ao adicionar produto: ' . $e->getMessage()];
    }
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
function atualizarQuantidade($produtoId, $novaQuantidade) {
    if (!isset($_SESSION['usuario_id'])) {
        return ['success' => false, 'erro' => 'Usuário não logado'];
    }

    $clienteId = $_SESSION['usuario_id'];

    try {
        $pdo = Database::getConnection();
        $produtoDao = new ProdutoDAO($pdo);
        $produto = $produtoDao->buscarPorId($produtoId);

        if (!$produto) {
            return ['success' => false, 'erro' => 'Produto não encontrado'];
        }

        $estoqueDisponivel = $produto->getQuantidade();

        if ($novaQuantidade <= 0) {
            // Se a quantidade for zero ou menos, remover o item do carrinho
            // A lógica de removerDoCarrinho pode ser chamada aqui ou duplicada/adaptada
            $carrinho = obterCarrinhoAtual();
            unset($carrinho[$produtoId]);
            $_SESSION['carrinhos'][$clienteId] = $carrinho;
            return ['success' => true, 'mensagem' => 'Produto removido do carrinho.', 'removido' => true];
        }

        if ($novaQuantidade > $estoqueDisponivel) {
            return ['success' => false, 'erro' => 'Estoque insuficiente. Quantidade disponível: ' . $estoqueDisponivel];
        }

        $carrinho = obterCarrinhoAtual();
        $carrinho[$produtoId] = $novaQuantidade;
        $_SESSION['carrinhos'][$clienteId] = $carrinho;
        
        return ['success' => true, 'mensagem' => 'Quantidade atualizada com sucesso.'];

    } catch (Exception $e) {
        error_log("Erro ao atualizar quantidade no carrinho: " . $e->getMessage());
        return ['success' => false, 'erro' => 'Erro ao atualizar quantidade: ' . $e->getMessage()];
    }
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
        
        foreach ($carrinho as $produtoId => $quantidadeNoCarrinho) {
            $produto = $produtoDao->buscarPorId($produtoId);
            if ($produto) {
                $produtos[] = [
                    'id' => $produto->getId(),
                    'nome' => $produto->getNome(),
                    'preco' => $produto->getPreco(),
                    'quantidade' => $quantidadeNoCarrinho, // Quantidade que o usuário tem no carrinho
                    'estoque_disponivel' => $produto->getQuantidade(), // Estoque total do produto
                    'subtotal' => $produto->getPreco() * $quantidadeNoCarrinho
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
            // Se for uma requisição AJAX esperando JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'erro' => 'Usuário não está logado', 'login_requerido' => true]);
            } else {
                header('Location: ../view/login.php');
            }
            exit;
        }

        $acao = $_POST['acao'] ?? '';
        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 1);
        $resposta = null;

        switch ($acao) {
            case 'adicionar':
                $resposta = adicionarAoCarrinho($produtoId, $quantidade);
                break;
            case 'remover':
                removerDoCarrinho($produtoId); 
                $resposta = ['success' => true, 'mensagem' => 'Produto removido com sucesso.', 'removido' => true]; 
                break;
            case 'atualizar':
                $resposta = atualizarQuantidade($produtoId, $quantidade);
                break;
            case 'limpar':
                limparCarrinho(); // Pode precisar retornar JSON
                $resposta = ['success' => true, 'mensagem' => 'Carrinho limpo com sucesso.']; // Assumindo sucesso
                break;
            default:
                $resposta = ['success' => false, 'erro' => 'Ação desconhecida.'];
        }

        // Se a resposta não for nula (ou seja, uma ação que retorna JSON, como 'adicionar')
        if ($resposta !== null) {
            header('Content-Type: application/json');
            echo json_encode($resposta);
            exit;
        }

        // Redirecionar de volta para a página anterior ou para o carrinho (comportamento antigo para ações não AJAX)
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
