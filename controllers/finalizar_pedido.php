<?php
// Configurações iniciais
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    error_log("DEBUG: Iniciando finalizar_pedido.php");

    require_once '../config/database.php';
    error_log("DEBUG: database.php carregado");

    require_once '../dao/produto_dao.php';
    error_log("DEBUG: produto_dao.php carregado");

    require_once '../dao/pedido_dao.php';
    error_log("DEBUG: pedido_dao.php carregado");

    define('CARRINHO_LOGIC_ONLY', true); // Definir flag ANTES de incluir carrinho.php
    require_once '../controllers/carrinho.php';
    error_log("DEBUG: carrinho.php carregado (apenas lógica)");
    
    // Verifica se há itens no carrinho
    $produtosCarrinho = obterProdutosCarrinho();
    error_log("DEBUG: produtosCarrinho: " . print_r($produtosCarrinho, true));
    
    if (empty($produtosCarrinho)) {
        responderErro('Seu carrinho está vazio');
    }

    $pdo = Database::getConnection();
    $pedidoDAO = new PedidoDAO($pdo);
    error_log("DEBUG: PedidoDAO instanciado");

    $clienteId = $_SESSION['usuario_id'];
    
    error_log("DEBUG: Chamando pedidoDAO->criarPedido() com clienteId: $clienteId e carrinho: " . print_r($produtosCarrinho, true));
    $resultado = $pedidoDAO->criarPedido($clienteId, $produtosCarrinho);
    error_log("DEBUG: Resultado de criarPedido: " . print_r($resultado, true));
    
    // Limpar o carrinho após a finalização do pedido
    limparCarrinho();
    error_log("DEBUG: Carrinho limpo");
    
    error_log("DEBUG: Chamando responderSucesso() com resultado: " . print_r($resultado, true));
    responderSucesso([
        'id' => $resultado['id'],
        'numero' => $resultado['numero'],
        'valor_total' => number_format($resultado['valor_total'], 2, ',', '.')
    ]);
    
} catch (Exception $e) {
    error_log("EXCEÇÃO em finalizar_pedido.php: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString());
    responderErro('Erro ao finalizar o pedido: ' . $e->getMessage());
}
?> 