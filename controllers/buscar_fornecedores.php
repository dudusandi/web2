<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/fornecedor_dao.php';
require_once __DIR__ . '/../model/fornecedor.php';
require_once __DIR__ . '/../model/endereco.php';

try {
    $pdo = Database::getConnection();
    $fornecedorDao = new FornecedorDAO($pdo);

    $termo = $_GET['termo'] ?? '';
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $itensPorPagina = 6;
    $offset = ($pagina - 1) * $itensPorPagina;

    if (!empty($termo)) {
        $fornecedores = $fornecedorDao->buscarFornecedoresDinamicos($termo, $itensPorPagina, $offset);
        $totalFornecedores = $fornecedorDao->contarFornecedoresBuscados($termo);
    } else {
        $fornecedores = $fornecedorDao->listarTodos($itensPorPagina, $offset);
        $totalFornecedores = $fornecedorDao->contarTodos();
    }

    $resultados = [
        'fornecedores' => array_map(function($fornecedor) {
            $endereco = $fornecedor->getEndereco();
            return [
                'id' => $fornecedor->getId(),
                'nome' => $fornecedor->getNome(),
                'descricao' => $fornecedor->getDescricao(),
                'telefone' => $fornecedor->getTelefone(),
                'email' => $fornecedor->getEmail(),
                'endereco' => [
                    'rua' => $endereco->getRua(),
                    'numero' => $endereco->getNumero(),
                    'bairro' => $endereco->getBairro(),
                    'cidade' => $endereco->getCidade(),
                    'estado' => $endereco->getEstado(),
                    'cep' => $endereco->getCep()
                ]
            ];
        }, $fornecedores),
        'total' => $totalFornecedores,
        'pagina_atual' => $pagina,
        'itens_por_pagina' => $itensPorPagina
    ];

    header('Content-Type: application/json');
    echo json_encode($resultados);
} catch (Exception $e) {
    error_log(date('[Y-m-d H:i:s] ') . "Erro em buscar_fornecedores: " . $e->getMessage() . PHP_EOL);
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Erro ao buscar fornecedores: ' . $e->getMessage()]);
}