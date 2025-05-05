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

// Inicialização de variáveis para mensagens
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Cliente - UcsExpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="cadastro.css">
</head>
<body>
    <!-- Cabeçalho -->
    <div class="header">
        <div class="logo">UCS<span>express</span></div>
    </div>

    <div class="container">
        <!-- Mensagens -->
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?= $tipoMensagem === 'erro' ? 'erro' : 'sucesso' ?>">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <div class="form-section">
            <h2>Cadastro de Cliente</h2>

            <form action="../controllers/cliente_controller.php" method="POST">
                <input type="hidden" name="acao" value="cadastrar">

                <div class="form-group">
                    <label for="nome">Nome *</label>
                    <input type="text" id="nome" name="nome" value="" required>
                    <?php if ($campoErro === 'nome'): ?>
                        <div class="error-message">Campo obrigatório</div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="" required>
                    <?php if ($campoErro === 'email'): ?>
                        <div class="error-message">Campo obrigatório ou email inválido</div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="senha">Senha *</label>
                    <input type="password" id="senha" name="senha" value="" required>
                    <?php if ($campoErro === 'senha'): ?>
                        <div class="error-message">Campo obrigatório</div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="telefone">Telefone *</label>
                    <input type="tel" id="telefone" name="telefone" value="" required>
                    <?php if ($campoErro === 'telefone'): ?>
                        <div class="error-message">Campo obrigatório</div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="cartao_credito">Cartão de Crédito</label>
                    <input type="text" id="cartao_credito" name="cartao_credito" value="">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rua">Rua *</label>
                        <input type="text" id="rua" name="rua" value="" required>
                        <?php if ($campoErro === 'rua'): ?>
                            <div class="error-message">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="numero">Número *</label>
                        <input type="text" id="numero" name="numero" value="" required>
                        <?php if ($campoErro === 'numero'): ?>
                            <div class="error-message">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="complemento">Complemento</label>
                    <input type="text" id="complemento" name="complemento" value="">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bairro">Bairro *</label>
                        <input type="text" id="bairro" name="bairro" value="" required>
                        <?php if ($campoErro === 'bairro'): ?>
                            <div class="error-message">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="cep">CEP *</label>
                        <input type="text" id="cep" name="cep" value="" required>
                        <?php if ($campoErro === 'cep'): ?>
                            <div class="error-message">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cidade">Cidade *</label>
                        <input type="text" id="cidade" name="cidade" value="" required>
                        <?php if ($campoErro === 'cidade'): ?>
                            <div class="error-message">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado *</label>
                        <input type="text" id="estado" name="estado" value="" required>
                        <?php if ($campoErro === 'estado'): ?>
                            <div class="error-message">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit">Cadastrar</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>