<?php
require_once '../config/database.php';
require_once '../dao/produto_dao.php';

// Inicializa o carrinho se não existir
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Função para adicionar produto ao carrinho
function adicionarAoCarrinho($produtoId, $quantidade = 1) {
    error_log("Tentando adicionar produto ID: $produtoId, Quantidade: $quantidade");
    error_log("Carrinho antes: " . print_r($_SESSION['carrinho'], true));
    
    if (isset($_SESSION['carrinho'][$produtoId])) {
        $_SESSION['carrinho'][$produtoId] += $quantidade;
    } else {
        $_SESSION['carrinho'][$produtoId] = $quantidade;
    }
    
    error_log("Carrinho depois: " . print_r($_SESSION['carrinho'], true));
}

// Função para remover produto do carrinho
function removerDoCarrinho($produtoId) {
    if (isset($_SESSION['carrinho'][$produtoId])) {
        unset($_SESSION['carrinho'][$produtoId]);
    }
}

// Função para atualizar quantidade
function atualizarQuantidade($produtoId, $quantidade) {
    if ($quantidade > 0) {
        $_SESSION['carrinho'][$produtoId] = $quantidade;
    } else {
        removerDoCarrinho($produtoId);
    }
}

// Função para limpar carrinho
function limparCarrinho() {
    $_SESSION['carrinho'] = [];
}

// Função para obter produtos do carrinho
function obterProdutosCarrinho() {
    if (empty($_SESSION['carrinho'])) {
        return [];
    }

    try {
        $pdo = Database::getConnection();
        $produtoDao = new ProdutoDAO($pdo);
        
        $produtos = [];
        foreach ($_SESSION['carrinho'] as $produtoId => $quantidade) {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST recebido: " . print_r($_POST, true));
    
    $acao = $_POST['acao'] ?? '';
    $produtoId = (int)($_POST['produto_id'] ?? 0);
    $quantidade = (int)($_POST['quantidade'] ?? 1);

    error_log("Ação: $acao, Produto ID: $produtoId, Quantidade: $quantidade");

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
    error_log("Redirecionando para: $redirect");
    header('Location: ' . $redirect);
    exit;
}

// Retornar dados do carrinho em JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'produtos' => obterProdutosCarrinho(),
        'total' => calcularTotalCarrinho()
    ]);
    exit;
}
