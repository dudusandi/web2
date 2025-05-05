<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/fornecedor_dao.php';
require_once '../model/fornecedor.php';

// Busca todos os fornecedores
try {
    $fornecedorDAO = new FornecedorDAO(Database::getConnection());
    $fornecedores = $fornecedorDAO->listarTodos();
} catch (Exception $e) {
    $fornecedores = [];
    $mensagem = "Erro ao listar fornecedores: " . $e->getMessage();
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
    <title>Gerenciar Fornecedores - UcsExpress</title>
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

        <!-- Botão para Adicionar Novo Fornecedor -->
        <div class="d-flex justify-content-between mb-4">
            <h2>Gerenciar Fornecedores</h2>
            <div>
                <a href="cadastro_fornecedor.php" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Adicionar Fornecedor
                </a>
                <a href="dashboard.php" class="btn btn-secondary">Voltar ao Dashboard</a>
            </div>
        </div>


        <!-- Listagem de Fornecedores -->
        <?php if (empty($fornecedores)): ?>
            <div class="empty-state">
                <i class="bi bi-building" style="font-size: 3rem;"></i>
                <h3 class="mt-3">Nenhum fornecedor cadastrado</h3>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($fornecedores as $fornecedor): ?>
                    <?php $endereco = $fornecedor->getEndereco(); ?>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($fornecedor->getNome(), ENT_QUOTES, 'UTF-8') ?></h5>
                                <p class="card-text text-muted">
                                    <strong>Descrição:</strong> <?= htmlspecialchars($fornecedor->getDescricao() ?? 'Nenhuma', ENT_QUOTES, 'UTF-8') ?><br>
                                    <strong>Telefone:</strong> <?= htmlspecialchars($fornecedor->getTelefone(), ENT_QUOTES, 'UTF-8') ?><br>
                                    <strong>Email:</strong> <?= htmlspecialchars($fornecedor->getEmail(), ENT_QUOTES, 'UTF-8') ?><br>
                                    <strong>Endereço:</strong> 
                                    <?= htmlspecialchars($endereco->getRua() . ', ' . $endereco->getNumero() . ', ' . $endereco->getBairro() . ', ' . $endereco->getCidade() . ' - ' . $endereco->getEstado(), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <a href="editar_fornecedor.php?id=<?= $fornecedor->getId() ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?= $fornecedor->getId() ?>, '<?= htmlspecialchars($fornecedor->getNome(), ENT_QUOTES, 'UTF-8') ?>')">
                                    <i class="bi bi-trash"></i> Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o fornecedor "<span id="confirmFornecedorNome"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a id="btnConfirmarExclusao" href="#" class="btn btn-danger">Excluir</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarExclusao(id, nome) {
            document.getElementById('confirmFornecedorNome').textContent = nome;
            document.getElementById('btnConfirmarExclusao').href = `../controllers/excluir_fornecedor.php?id=${id}`;
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            confirmModal.show();
        }
    </script>
</body>
</html>