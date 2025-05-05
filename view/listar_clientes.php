<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/cliente_dao.php';
require_once '../model/cliente.php';

// Busca todos os clientes
try {
    $clienteDAO = new ClienteDAO(Database::getConnection());
    $clientes = $clienteDAO->listarTodos();
} catch (Exception $e) {
    $clientes = [];
    $mensagem = "Erro ao listar clientes: " . $e->getMessage();
    $tipoMensagem = 'erro';
}

// Mensagens de feedback
$mensagem = $_GET['mensagem'] ?? '';
$tipoMensagem = $_GET['tipo_mensagem'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Clientes - UcsExpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }
        .header .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .header .logo span {
            color: #ffca2c;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: scale(1.02);
        }
        .empty-state {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Cabeçalho -->
    <div class="header">
        <div class="logo">UCS<span>express</span></div>
    </div>

    <div class="container">
        <!-- Mensagens -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Botões de Navegação -->
        <div class="d-flex justify-content-between mb-4">
            <h2>Listar Clientes</h2>
            <div>
                <a href="cadastro_cliente.php" class="btn btn-primary me-2">
                    <i class="bi bi-plus"></i> Cadastrar Novo
                </a>
                <a href="dashboard.php" class="btn btn-secondary">Voltar ao Dashboard</a>
            </div>
        </div>

        <!-- Listagem de Clientes -->
        <?php if (empty($clientes)): ?>
            <div class="empty-state">
                <i class="bi bi-person" style="font-size: 3rem;"></i>
                <h3 class="mt-3">Nenhum cliente cadastrado</h3>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($clientes as $cliente): ?>
                    <?php $endereco = $cliente->getEndereco(); ?>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($cliente->getNome(), ENT_QUOTES, 'UTF-8') ?></h5>
                                <p class="card-text text-muted">
                                    <strong>Telefone:</strong> <?= htmlspecialchars($cliente->getTelefone(), ENT_QUOTES, 'UTF-8') ?><br>
                                    <strong>Email:</strong> <?= htmlspecialchars($cliente->getEmail(), ENT_QUOTES, 'UTF-8') ?><br>
                                    <strong>Cartão de Crédito:</strong> <?= htmlspecialchars($cliente->getCartaoCredito(), ENT_QUOTES, 'UTF-8') ?><br>
                                    <strong>Endereço:</strong> 
                                    <?= htmlspecialchars($endereco->getRua() . ', ' . $endereco->getNumero() . ', ' . $endereco->getBairro() . ', ' . $endereco->getCidade() . ' - ' . $endereco->getEstado(), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <a href="editar_cliente.php?id=<?= $cliente->getId() ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>