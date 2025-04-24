<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once __DIR__ . '/.../view/config/database.php';
require_once __DIR__ . '/.../view/dao/produto_dao.php';
require_once __DIR__ . '/.../view/model/produto.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);

    // Verificar ID do produto
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        header('Location: ../view/lista_produtos.php?mensagem=ID inválido&tipo_mensagem=erro');
        exit;
    }

    // Verificar se o produto existe
    $produto = $produtoDao->buscarPorId($id);
    if (!$produto) {
        header('Location: ../view/lista_produtos.php?mensagem=Produto não encontrado&tipo_mensagem=erro');
        exit;
    }

    // Remover produto
    if ($produtoDao->removerProduto($id)) {
        header('Location: ../view/lista_produtos.php?mensagem=Produto excluído com sucesso&tipo_mensagem=sucesso');
        exit;
    } else {
        header('Location: ../view/lista_produtos.php?mensagem=Erro ao excluir o produto&tipo_mensagem=erro');
        exit;
    }
} catch (Exception $e) {
    error_log("Erro em excluir_produto.php: " . $e->getMessage());
    header('Location: ../view/lista_produtos.php?mensagem=Erro ao excluir: ' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
    exit;
}