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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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


            <div class="row mb-3">
            <div class="col">
                    <label for="cep" class="form-label">CEP *</label>
                    <input type="text" class="form-control <?= $campoErro === 'cep' ? 'is-invalid' : '' ?>" id="cep" name="cep" required>
                    <small class="text-muted">*Preenchimento Automático</small>
                </div>
                <div class="col">
                    <label for="bairro" class="form-label">Bairro *</label>
                    <input type="text" class="form-control <?= $campoErro === 'bairro' ? 'is-invalid' : '' ?>" id="bairro" name="bairro" required>
                </div>
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



            <div class="row mb-4">
                <div class="col">
                    <label for="estado" class="form-label">Estado *</label>
                    <select class="form-control <?= $campoErro === 'estado' ? 'is-invalid' : '' ?>" id="estado" name="estado" required>
                        <option value="">Selecione um estado</option>
                    </select>
                </div>
                <div class="col">
                    <label for="cidade" class="form-label">Cidade *</label>
                    <select class="form-control <?= $campoErro === 'cidade' ? 'is-invalid' : '' ?>" id="cidade" name="cidade" required disabled>
                        <option value="">Selecione um estado primeiro</option>
                    </select>
                </div>
            </div>


            <div class="d-flex justify-content-between mt-4">
                    <button type="submit" class="btn btn-primary">Cadastrar Cliente</button>
                    <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
                </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Carrega os estados ao carregar a página
    $.getJSON('https://servicodados.ibge.gov.br/api/v1/localidades/estados', function(data) {
        var items = [];
        $.each(data, function(key, val) {
            items.push('<option value="' + val.sigla + '">' + val.nome + '</option>');
        });
        $('#estado').html(items.join(''));
    });

    // Quando o estado é selecionado, carrega as cidades correspondentes
    $('#estado').change(function() {
        var uf = $(this).val();
        if (uf) {
            $('#cidade').prop('disabled', false);
            $.getJSON('https://servicodados.ibge.gov.br/api/v1/localidades/estados/' + uf + '/municipios', function(data) {
                var items = [];
                $.each(data, function(key, val) {
                    items.push('<option value="' + val.nome + '">' + val.nome + '</option>');
                });
                $('#cidade').html(items.join(''));
            });
        } else {
            $('#cidade').prop('disabled', true).html('<option value="">Selecione um estado primeiro</option>');
        }
    });

    // Autocompletar endereço via CEP usando ViaCEP
    $('#cep').blur(function() {
        var cep = $(this).val().replace(/\D/g, '');
        if (cep.length === 8) {
            $.getJSON('https://viacep.com.br/ws/' + cep + '/json/', function(data) {
                if (!data.erro) {
                    $('#rua').val(data.logradouro);
                    $('#bairro').val(data.bairro);
                    $('#complemento').val(data.complemento);
                    $('#cidade').val(data.localidade);
                    $('#estado').val(data.uf).trigger('change');
                } else {
                    alert('CEP não encontrado');
                }
            }).fail(function() {
                alert('Erro ao consultar CEP');
            });
        }
    });
});
</script>
</body>
</html>