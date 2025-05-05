<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Inclui os arquivos necessários
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/cliente_dao.php';
require_once __DIR__ . '/../model/cliente.php';
require_once __DIR__ . '/../model/endereco.php';

// Inicializa a conexão com o banco de dados e o DAO
try {
    $pdo = Database::getConnection();
    $clienteDao = new ClienteDAO($pdo);

    // Valida e obtém os parâmetros da requisição
    $termo = isset($_GET['termo']) ? trim((string)$_GET['termo']) : '';
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1; // Garante que a página seja pelo menos 1
    $itensPorPagina = 6; // Valor fixo, pode ser ajustado ou passado como parâmetro se necessário
    $offset = ($pagina - 1) * $itensPorPagina;

    // Executa a busca com base no termo
    $clientes = [];
    $totalClientes = 0;

    if (!empty($termo)) {
        $clientes = $clienteDao->buscarClientesDinamicos($termo, $itensPorPagina, $offset);
        $totalClientes = $clienteDao->contarClientesBuscados($termo);
    } else {
        $clientes = $clienteDao->listarTodos($itensPorPagina, $offset);
        $totalClientes = $clienteDao->contarTodos();
    }

    // Formata os resultados para o JSON
    $resultados = [
        'clientes' => array_map(function ($cliente) {
            $endereco = $cliente->getEndereco();
            return [
                'id' => $cliente->getId(),
                'nome' => htmlspecialchars($cliente->getNome(), ENT_QUOTES, 'UTF-8'),
                'telefone' => htmlspecialchars($cliente->getTelefone(), ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($cliente->getEmail(), ENT_QUOTES, 'UTF-8'),
                'cartao_credito' => htmlspecialchars($cliente->getCartaoCredito(), ENT_QUOTES, 'UTF-8'),
                'endereco' => [
                    'rua' => htmlspecialchars($endereco->getRua(), ENT_QUOTES, 'UTF-8'),
                    'numero' => htmlspecialchars($endereco->getNumero(), ENT_QUOTES, 'UTF-8'),
                    'bairro' => htmlspecialchars($endereco->getBairro(), ENT_QUOTES, 'UTF-8'),
                    'cidade' => htmlspecialchars($endereco->getCidade(), ENT_QUOTES, 'UTF-8'),
                    'estado' => htmlspecialchars($endereco->getEstado(), ENT_QUOTES, 'UTF-8'),
                    'cep' => htmlspecialchars($endereco->getCep(), ENT_QUOTES, 'UTF-8'),
                    'complemento' => htmlspecialchars($endereco->getComplemento() ?? '', ENT_QUOTES, 'UTF-8')
                ]
            ];
        }, $clientes),
        'total' => $totalClientes,
        'pagina_atual' => $pagina,
        'itens_por_pagina' => $itensPorPagina,
        'total_paginas' => ceil($totalClientes / $itensPorPagina) // Adiciona o número total de páginas
    ];

    // Define o cabeçalho e retorna o JSON
    header('Content-Type: application/json');
    echo json_encode($resultados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    // Loga o erro e retorna uma resposta de erro
    error_log(date('[Y-m-d H:i:s] ') . "Erro em buscar_clientes: " . $e->getMessage() . PHP_EOL);
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao buscar clientes: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}