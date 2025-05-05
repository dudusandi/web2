<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Inclui as dependências
require_once '../config/database.php';
require_once '../dao/fornecedor_dao.php';
require_once '../model/fornecedor.php';
require_once '../model/endereco.php';

// Verifica se o ID do fornecedor foi fornecido
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: listar_fornecedor.php?mensagem=ID do fornecedor não fornecido');
    exit;
}

// Busca o fornecedor pelo ID
try {
    $fornecedorDAO = new FornecedorDAO(Database::getConnection());
    $fornecedor = $fornecedorDAO->buscarPorId($id);
    if (!$fornecedor) {
        header('Location: listar_fornecedor.php?mensagem=Fornecedor não encontrado');
        exit;
    }
} catch (Exception $e) {
    header('Location: listar_fornecedor.php?mensagem=Erro ao buscar fornecedor: ' . urlencode($e->getMessage()));
    exit;
}

// Extrai os dados do fornecedor e do endereço
$endereco = $fornecedor->getEndereco();
$mensagem = $_GET['mensagem'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Fornecedor - UcsExpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin-top: 30px;
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
        .form-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .form-section h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
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
            <div class="alert alert-<?php echo strpos($mensagem, 'Erro') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário de Edição -->
        <div class="form-section">
            <h2>Editar Fornecedor</h2>
            <form id="formFornecedor" action="../controllers/fornecedor_controller.php" method="POST" onsubmit="return validarFormulario()">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" value="<?= $fornecedor->getId() ?>">

                <!-- Dados do Fornecedor -->
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome *</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($fornecedor->getNome(), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($fornecedor->getDescricao() ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="telefone" class="form-label">Telefone *</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($fornecedor->getTelefone(), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($fornecedor->getEmail(), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <!-- Endereço -->
                <h5 class="mt-4">Endereço</h5>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="rua" class="form-label">Rua *</label>
                        <input type="text" class="form-control" id="rua" name="rua" value="<?= htmlspecialchars($endereco->getRua(), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="numero" class="form-label">Número *</label>
                        <input type="text" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($endereco->getNumero(), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="complemento" class="form-label">Complemento</label>
                    <input type="text" class="form-control" id="complemento" name="complemento" value="<?= htmlspecialchars($endereco->getComplemento() ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="bairro" class="form-label">Bairro *</label>
                        <input type="text" class="form-control" id="bairro" name="bairro" value="<?= htmlspecialchars($endereco->getBairro(), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cep" class="form-label">CEP *</label>
                        <input type="text" class="form-control" id="cep" name="cep" value="<?= htmlspecialchars($endereco->getCep(), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cidade" class="form-label">Cidade *</label>
                        <input type="text" class="form-control" id="cidade" name="cidade" value="<?= htmlspecialchars($endereco->getCidade(), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="estado" class="form-label">Estado *</label>
                        <select class="form-control" id="estado" name="estado" required>
                            <option value="">Selecione</option>
                            <option value="AC" <?= $endereco->getEstado() === 'AC' ? 'selected' : '' ?>>Acre</option>
                            <option value="AL" <?= $endereco->getEstado() === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                            <option value="AP" <?= $endereco->getEstado() === 'AP' ? 'selected' : '' ?>>Amapá</option>
                            <option value="AM" <?= $endereco->getEstado() === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                            <option value="BA" <?= $endereco->getEstado() === 'BA' ? 'selected' : '' ?>>Bahia</option>
                            <option value="CE" <?= $endereco->getEstado() === 'CE' ? 'selected' : '' ?>>Ceará</option>
                            <option value="DF" <?= $endereco->getEstado() === 'DF' ? 'selected' : '' ?>>Distrito Federal</option>
                            <option value="ES" <?= $endereco->getEstado() === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                            <option value="GO" <?= $endereco->getEstado() === 'GO' ? 'selected' : '' ?>>Goiás</option>
                            <option value="MA" <?= $endereco->getEstado() === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                            <option value="MT" <?= $endereco->getEstado() === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                            <option value="MS" <?= $endereco->getEstado() === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                            <option value="MG" <?= $endereco->getEstado() === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                            <option value="PA" <?= $endereco->getEstado() === 'PA' ? 'selected' : '' ?>>Pará</option>
                            <option value="PB" <?= $endereco->getEstado() === 'PB' ? 'selected' : '' ?>>Paraíba</option>controller
                            <option value="PR" <?= $endereco->getEstado() === 'PR' ? 'selected' : '' ?>>Paraná</option>
                            <option value="PE" <?= $endereco->getEstado() === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                            <option value="PI" <?= $endereco->getEstado() === 'PI' ? 'selected' : '' ?>>Piauí</option>
                            <option value="RJ" <?= $endereco->getEstado() === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                            <option value="RN" <?= $endereco->getEstado() === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option>
                            <option value="RS" <?= $endereco->getEstado() === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option>
                            <option value="RO" <?= $endereco->getEstado() === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                            <option value="RR" <?= $endereco->getEstado() === 'RR' ? 'selected' : '' ?>>Roraima</option>
                            <option value="SC" <?= $endereco->getEstado() === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                            <option value="SP" <?= $endereco->getEstado() === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                            <option value="SE" <?= $endereco->getEstado() === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                            <option value="TO" <?= $endereco->getEstado() === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                        </select>
                    </div>
                </div>

                <!-- Botões -->
                <div class="d-flex justify-content-between mt-4">
                    <a href="listar_fornecedor.php" class="btn btn-secondary">Voltar</a>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validarFormulario() {
            const form = document.getElementById('formFornecedor');
            const nome = document.getElementById('nome').value.trim();
            const telefone = document.getElementById('telefone').value.trim();
            const email = document.getElementById('email').value.trim();
            const rua = document.getElementById('rua').value.trim();
            const numero = document.getElementById('numero').value.trim();
            const bairro = document.getElementById('bairro').value.trim();
            const cep = document.getElementById('cep').value.trim();
            const cidade = document.getElementById('cidade').value.trim();
            const estado = document.getElementById('estado').value;

            // Validação de campos obrigatórios
            if (!nome || !telefone || !email || !rua || !numero || !bairro || !cep || !cidade || !estado) {
                alert('Por favor, preencha todos os campos obrigatórios (*).');
                return false;
            }

            // Validação de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Por favor, insira um email válido.');
                return false;
            }

            // Validação de telefone (exemplo simples: apenas números e pelo menos 10 dígitos)
            const telefoneRegex = /^\d{10,}$/;
            if (!telefoneRegex.test(telefone.replace(/\D/g, ''))) {
                alert('Por favor, insira um telefone válido (mínimo 10 dígitos).');
                return false;
            }

            // Validação de CEP (exemplo simples: 8 dígitos)
            const cepRegex = /^\d{8}$/;
            if (!cepRegex.test(cep.replace(/\D/g, ''))) {
                alert('Por favor, insira um CEP válido (8 dígitos).');
                return false;
            }

            return true;
        }
    </script>
</body>
</html>