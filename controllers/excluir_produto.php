<?php

define('BASE_PATH', realpath(dirname(__DIR__)));

require_once BASE_PATH . '/model/produto.php';
require_once BASE_PATH . '/dao/produto_dao.php';
require_once BASE_PATH . '/config/database.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        header('Location: ../view/lista_produtos.php?mensagem=ID inválido&tipo_mensagem=erro');
        exit;
    }

    $produto = $produtoDao->buscarPorId($id);
    if (!$produto) {
        header('Location: ../view/listar_produtos.php?mensagem=Produto não encontrado&tipo_mensagem=erro');
        exit;
    }

    if ($produtoDao->removerProduto($id)) {
        header('Location: ../view/listar_produtos.php?mensagem=Produto excluído com sucesso&tipo_mensagem=sucesso');
        exit;
    } else {
        header('Location: ../view/listar_produtos.php?mensagem=Erro ao excluir o produto&tipo_mensagem=erro');
        exit;
    }
} catch (Exception $e) {
    error_log("Erro em excluir_produto.php: " . $e->getMessage());
    header('Location: ../view/listar_produtos.php?mensagem=Erro ao excluir: ' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
    exit;
}