<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../model/produto.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);

    $termo = $_GET['termo'] ?? '';
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $itensPorPagina = 6;
    $offset = ($pagina - 1) * $itensPorPagina;

    if (!empty($termo)) {
        $produtos = $produtoDao->buscarProdutosDinamicos($termo, $itensPorPagina, $offset);
        $totalProdutos = $produtoDao->contarProdutosBuscados($termo);
    } else {
        $produtos = $produtoDao->listarTodosProdutos($itensPorPagina, $offset);
        $totalProdutos = $produtoDao->contarTodosProdutos();
    }

    $resultados = [
        'produtos' => array_map(function($produto) {
            return [
                'id' => $produto->getId(),
                'nome' => $produto->getNome(),
                'descricao' => $produto->getDescricao(),
                'foto' => $produto->getFoto(),
                'quantidade' => $produto->getQuantidade(),
                'preco' => $produto->getPreco(),
                'fornecedor_id' => $produto->getFornecedorId(),
                'usuario_id' => $produto->getUsuarioId(),
                'fornecedor_nome' => $produto->fornecedor_nome
            ];
        }, $produtos),
        'total' => $totalProdutos,
        'pagina_atual' => $pagina,
        'itens_por_pagina' => $itensPorPagina
    ];

    header('Content-Type: application/json');
    echo json_encode($resultados);
} catch (Exception $e) {
    error_log(date('[Y-m-d H:i:s] ') . "Erro em buscar_produtos: " . $e->getMessage() . PHP_EOL);
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}