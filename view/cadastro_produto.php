<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /web2/view/login.php');
    exit;
}
$erro = $_GET['erro'] ?? '';
$campo = $_GET['campo'] ?? '';
$sucesso = isset($_GET['sucesso']) && $_GET['sucesso'] == '1';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .error-message { color: red; }
        .success-message { color: green; }
        .form-container { max-width: 600px; margin: 50px auto; }
    </style>
</head>
<body>
    <div class="container form-container">
        <h2 class="mb-4">Cadastro de Produto</h2>

        <!-- Mensagens de feedback -->
        <?php if ($sucesso): ?>
            <div class="alert alert-success success-message">
                Produto cadastrado com sucesso!
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-danger error-message">
                <?php
                switch ($erro) {
                    case 'campos_obrigatorios':
                        echo "O campo '$campo' é obrigatório.";
                        break;
                    case 'nome_existente':
                        echo "O nome do produto já está cadastrado.";
                        break;
                    case 'estoque_invalido':
                        echo "O estoque deve ser um número não negativo.";
                        break;
                    case 'foto_invalida':
                        echo "A foto deve ser uma imagem válida (JPEG, PNG, GIF) e menor que 2MB.";
                        break;
                    case 'erro_sistema':
                        echo "Ocorreu um erro no sistema. Tente novamente.";
                        break;
                    default:
                        echo "Erro desconhecido.";
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de cadastro -->
        <form action="/web2/controller/cadastro_produto.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome do Produto *</label>
                <input type="text" class="form-control" id="nome" name="nome" required
                       value="<?= htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="4"><?= htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="mb-3">
                <label for="foto" class="form-label">Foto do Produto</label>
                <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg,image/png,image/gif">
                <small class="form-text text-muted">Formatos aceitos: JPEG, PNG, GIF. Tamanho máximo: 2MB. Escolha uma imagem clara para destacar seu produto.</small>
            </div>

            <div class="mb-3">
                <label for="fornecedor" class="form-label">Fornecedor *</label>
                <input type="text" class="form-control" id="fornecedor" name="fornecedor" required
                       value="<?= htmlspecialchars($_POST['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="mb-3">
                <label for="estoque" class="form-label">Estoque *</label>
                <input type="number" class="form-control" id="estoque" name="estoque" required min="0"
                       value="<?= htmlspecialchars($_POST['estoque'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <button type="submit" class="btn btn-primary">Cadastrar</button>
            <a href="../view/dashboard.php" class="btn btn-secondary">Voltar</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>