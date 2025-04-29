<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../dao/estoque_dao.php'; // Novo: importar o EstoqueDAO
require_once __DIR__ . '/../model/produto.php';
require_once __DIR__ . '/../model/estoque.php'; // Novo: importar a classe Estoque

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    $pdo = Database::getConnection();
    
    // Instanciar DAOs
    $produtoDao = new ProdutoDAO($pdo);
    $estoqueDao = new EstoqueDAO($pdo);

    // Buscar produto
    $produto = $produtoDao->buscarPorId($id);

    if ($produto) {
        // Buscar estoque relacionado ao produto
        $estoque = $estoqueDao->buscarPorProdutoId($id);

        echo json_encode([
            'nome' => $produto->getNome(),
            'descricao' => $produto->getDescricao(),
            'foto' => $produto->getFoto(),
            'fornecedor' => $produto->getFornecedor(),
            'estoque' => $estoque ? $estoque->getQuantidade() : 0 // Se não encontrar estoque, assume 0
        ]);
    } else {
        echo json_encode(['error' => 'Produto não encontrado']);
    }
} catch (Exception $e) {
    error_log("Erro em get_produto.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao buscar produto']);
}
?>
