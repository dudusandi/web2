<?php
// Limpa qualquer saída anterior
ob_start();
ob_clean();

session_start();
require_once '../config/database.php';
require_once '../dao/produto_dao.php';
require_once '../model/produto.php';

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json; charset=utf-8');

try {
    $termo = $_GET['termo'] ?? '';
    $pagina = (int)($_GET['pagina'] ?? 1);
    $itensPorPagina = (int)($_GET['itensPorPagina'] ?? 12);

    $pdo = Database::getConnection();
    $produtoDAO = new ProdutoDAO($pdo);
    
    $resultado = $produtoDAO->buscarProdutos($termo, $pagina, $itensPorPagina);
    $produtos = $resultado['produtos'];
    $total = $resultado['total'];

    $produtosArray = array_map(function($produto) {
        return [
            'id' => $produto['id'],
            'nome' => $produto['nome'],
            'descricao' => $produto['descricao'],
            'foto' => $produto['foto'] ? base64_encode($produto['foto']) : null,
            'fornecedor_id' => $produto['fornecedor_id'],
            'quantidade' => $produto['quantidade'],
            'preco' => $produto['preco'],
            'fornecedor_nome' => $produto['fornecedor_nome']
        ];
    }, $produtos);

    $response = [
        'success' => true,
        'produtos' => $produtosArray,
        'total' => $total,
        'pagina' => $pagina,
        'itensPorPagina' => $itensPorPagina
    ];

    // Limpa qualquer saída anterior
    ob_clean();
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Erro em buscar_produtos.php: " . $e->getMessage());
    http_response_code(500);
    
    // Limpa qualquer saída anterior
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar produtos: ' . $e->getMessage()
    ]);
}

// Envia a saída e limpa o buffer
ob_end_flush(); 