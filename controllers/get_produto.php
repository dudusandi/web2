<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../model/produto.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    $produto = $produtoDao->buscarPorId($id);

    if ($produto) {
        echo json_encode([
            'nome' => $produto->getNome(),
            'descricao' => $produto->getDescricao(),
            'foto' => $produto->getFoto(),
            'fornecedor' => $produto->getFornecedor(),
            'estoque' => $produto->getEstoque()
        ]);
    } else {
        echo json_encode(['error' => 'Produto não encontrado']);
    }
} catch (Exception $e) {
    error_log("Erro em get_produto.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao buscar produto']);
}