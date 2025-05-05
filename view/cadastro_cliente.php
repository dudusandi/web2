<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/cliente_dao.php';
require_once '../model/cliente.php';
require_once '../model/endereco.php';

$mensagem = $_GET['mensagem'] ?? '';
$tipoMensagem = $_GET['tipo_mensagem'] ?? '';
$campoErro = $_GET['campo'] ?? '';

try {
    $clienteDAO = new ClienteDAO(Database::getConnection());
} catch (Exception $e) {
    $mensagem = "Erro ao conectar ao banco de dados: " . $e->getMessage();
    $tipoMensagem = 'erro';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Cliente - UcsExpress</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="editar.css">
</head>
<body>

<div class="header">
    <div class="logo">UCS<span>express</span></div>
</div>

<div class="container">
    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> mt-3">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <div class="form-section mt-4">
        <h2 class="mb-4">Cadastro de Cliente</h2>

        <form action="../controllers/cliente_controller.php" method="POST">
            <input type="hidden" name="acao" value="cadastrar">

            <div class="row mb-3">
                <div class="col">
                    <label for="nome" class="form-label">Nome *</label>
                    <input type="text" class="form-control <?= $campoErro === 'nome' ? 'is-invalid' : '' ?>" id="nome" name="nome" required>
                </div>
                <div class="col">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control <?= $campoErro === 'email' ? 'is-invalid' : '' ?>" id="email" name="email" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label for="senha" class="form-label">Senha *</label>
                    <input type="password" class="form-control <?= $campoErro === 'senha' ? 'is-invalid' : '' ?>" id="senha" name="senha" required>
                </div>
                <div class="col">
                    <label for="telefone" class="form-label">Telefone *</label>
                    <input type="tel" class="form-control <?= $campoErro === 'telefone' ? 'is-invalid' : '' ?>" id="telefone" name="telefone" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="cartao_credito" class="form-label">Cartão de Crédito</label>
                <input type="text" class="form-control" id="cartao_credito" name="cartao_credito">
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label for="rua" class="form-label">Rua *</label>
                    <input type="text" class="form-control <?= $campoErro === 'rua' ? 'is-invalid' : '' ?>" id="rua" name="rua" required>
                </div>
                <div class="col">
                    <label for="numero" class="form-label">Número *</label>
                    <input type="text" class="form-control <?= $campoErro === 'numero' ? 'is-invalid' : '' ?>" id="numero" name="numero" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="complemento" class="form-label">Complemento</label>
                <input type="text" class="form-control" id="complemento" name="complemento">
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label for="bairro" class="form-label">Bairro *</label>
                    <input type="text" class="form-control <?= $campoErro === 'bairro' ? 'is-invalid' : '' ?>" id="bairro" name="bairro" required>
                </div>
                <div class="col">
                    <label for="cep" class="form-label">CEP *</label>
                    <input type="text" class="form-control <?= $campoErro === 'cep' ? 'is-invalid' : '' ?>" id="cep" name="cep" required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col">
                    <label for="cidade" class="form-label">Cidade *</label>
                    <input type="text" class="form-control <?= $campoErro === 'cidade' ? 'is-invalid' : '' ?>" id="cidade" name="cidade" required>
                </div>
                <div class="col">
                    <label for="estado" class="form-label">Estado *</label>
                    <input type="text" class="form-control <?= $campoErro === 'estado' ? 'is-invalid' : '' ?>" id="estado" name="estado" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Cadastrar Cliente</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
