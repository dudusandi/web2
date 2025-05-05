<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

$pdo = Database::getConnection();
$produtoDao = new ProdutoDAO($pdo);
$id = $_GET['id'] ?? null;

if ($id) {
    $produto = $produtoDao->buscarPorId((int)$id);
    if ($produto) {
        $response = [
            'nome' => $produto->getNome(),
            'descricao' => $produto->getDescricao(),
            'fornecedor' => (int)$produto->getFornecedorId(), 
            'fornecedor_nome' => $produto->fornecedor_nome ?? 'Sem fornecedor', 
            'estoque' => $produto->getQuantidade() ?? 0,
            'preco' => $produto->getPreco() ?? 0,
            'foto' => $produto->getFoto()
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Produto não encontrado']);
    }
} else {
    echo json_encode(['error' => 'ID não fornecido']);
}
?>