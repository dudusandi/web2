<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../dao/fornecedor_dao.php';
require_once __DIR__ . '/../dao/estoque_dao.php';
require_once __DIR__ . '/../model/produto.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    $fornecedorDao = new FornecedorDAO($pdo);
    $estoqueDao = new EstoqueDAO($pdo);

    $produto = $produtoDao->buscarPorId($id);

    if ($produto) {
        // Buscar o nome do fornecedor
        $fornecedorNome = $fornecedorDao->buscarNomePorId($produto->getFornecedorId());

        // Buscar a quantidade do estoque
        $quantidade = $estoqueDao->buscarQuantidadePorId($produto->getEstoqueId());

        // Retornar os dados do produto
        echo json_encode([
            'nome' => $produto->getNome(),
            'descricao' => $produto->getDescricao(),
            'foto' => $produto->getFoto(),
            'fornecedor' => $fornecedorNome,
            'estoque' => $quantidade
        ]);
    } else {
        echo json_encode(['error' => 'Produto não encontrado']);
    }
} catch (Exception $e) {
    error_log("Erro em get_produto.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao buscar produto']);
}
?>