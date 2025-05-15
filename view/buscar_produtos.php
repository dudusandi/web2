<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);

    $ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
    $produtos = [];

    foreach ($ids as $id) {
        $produto = $produtoDao->buscarPorId($id);
        if ($produto) {
            $produtos[] = [
                'id' => $produto->getId(),
                'nome' => $produto->getNome(),
                'preco' => $produto->getPreco(),
                'quantidade' => $produto->getQuantidade()
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($produtos);
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['erro' => $e->getMessage()]);
} 