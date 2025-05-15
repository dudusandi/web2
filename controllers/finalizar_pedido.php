<?php
// Configurações iniciais
error_reporting(0);
ini_set('display_errors', 0);

// Forçar saída de cabeçalho JSON
header('Content-Type: application/json; charset=utf-8');

// Verifica se a sessão já está ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Funções para resposta
function responderErro($mensagem) {
    echo json_encode([
        'success' => false,
        'mensagem' => $mensagem
    ]);
    exit;
}

function responderSucesso($pedido) {
    echo json_encode([
        'success' => true,
        'mensagem' => 'Pedido finalizado com sucesso!',
        'pedido' => $pedido
    ]);
    exit;
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    responderErro('Você precisa estar logado para finalizar o pedido');
}

try {
    require_once '../config/database.php';
    require_once '../dao/produto_dao.php';
    require_once '../dao/pedido_dao.php';
    require_once '../controllers/carrinho.php';
    
    // Verifica se há itens no carrinho
    $produtosCarrinho = obterProdutosCarrinho();
    
    if (empty($produtosCarrinho)) {
        responderErro('Seu carrinho está vazio');
    }

    $pdo = Database::getConnection();
    $pedidoDAO = new PedidoDAO($pdo);
    $clienteId = $_SESSION['usuario_id'];
    
    // Criar o pedido
    $resultado = $pedidoDAO->criarPedido($clienteId, $produtosCarrinho);
    
    // Limpar o carrinho após a finalização do pedido
    limparCarrinho();
    
    // Retornar dados do pedido criado
    responderSucesso([
        'id' => $resultado['id'],
        'numero' => $resultado['numero'],
        'valor_total' => number_format($resultado['valor_total'], 2, ',', '.')
    ]);
    
} catch (Exception $e) {
    responderErro('Erro ao finalizar o pedido: ' . $e->getMessage());
}
?> 