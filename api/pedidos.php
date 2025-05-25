<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); 
    echo json_encode([
        'success' => false, 
        'error' => 'Você não tem permissão para estar aqui.'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/pedido_dao.php';


$response = [];

try {
    $pdo = Database::getConnection();
    $pedidoDao = new PedidoDAO($pdo);

    $resultado = $pedidoDao->listarTodosPedidos($paginaAtual, $itensPorPagina, $termoBusca);

    if ($resultado && isset($resultado['pedidos'])) {
        $response = [
            'success' => true,
            'data' => [
                'pedidos' => $resultado['pedidos'],
            ]
        ];
    } else {
        $response = [
            'success' => true, 
            'data' => [
                'pedidos' => [], 
            ],
            'message' => 'Nenhum pedido encontrado'
        ];
    }

} catch (Exception $e) {
    http_response_code(500); 
    error_log("Erro na API de Pedidos (api/pedidos.php): " . $e->getMessage());
    $response = [
        'success' => false, 
        'error' => 'Erro',
    ];
}

echo json_encode($response);
exit;
?>
