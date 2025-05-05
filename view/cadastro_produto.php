<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ./login.php');
    exit;
}
$erro = $_GET['erro'] ?? '';
$campo = $_GET['campo'] ?? '';
$sucesso = isset($_GET['sucesso']) && $_GET['sucesso'] == '1';

// Manter valores preenchidos em caso de erro
$nome = htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8');
$descricao = htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8');
$fornecedorId = htmlspecialchars($_POST['fornecedor_id'] ?? '', ENT_QUOTES, 'UTF-8');
$quantidade = htmlspecialchars($_POST['quantidade'] ?? '0', ENT_QUOTES, 'UTF-8');
$preco = htmlspecialchars($_POST['preco'] ?? '0.00', ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color:rgb(235, 235, 235); }
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
                        echo "A quantidade e o preço devem ser números não negativos.";
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
        <form action="../controllers/cadastrar_produto.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome do Produto *</label>
                <input type="text" class="form-control" id="nome" name="nome" required value="<?= $nome ?>">
            </div>

            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="4"><?= $descricao ?></textarea>
            </div>

            <div class="mb-3">
                <label for="foto" class="form-label">Foto do Produto</label>
                <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg,image/png,image/gif">
                <small class="form-text text-muted">Formatos aceitos: JPEG, PNG, GIF. Tamanho máximo: 2MB.</small>
            </div>

            <div class="mb-3">
                <label for="fornecedor_id" class="form-label">Fornecedor *</label>
                <?php
                require_once '../config/database.php';
                $pdo = Database::getConnection();
                $stmt = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome");
                $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <select class="form-control" id="fornecedor_id" name="fornecedor_id" required>
                    <option value="">Selecione um fornecedor</option>
                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <option value="<?= $fornecedor['id'] ?>" <?= $fornecedorId == $fornecedor['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fornecedor['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="quantidade" class="form-label">Quantidade em Estoque *</label>
                <input type="number" class="form-control" id="quantidade" name="quantidade" required min="0" value="<?= $quantidade ?>">
            </div>

            <div class="mb-3">
                <label for="preco" class="form-label">Preço Unitário (R$) *</label>
                <input type="number" step="0.01" class="form-control" id="preco" name="preco" required min="0" value="<?= $preco ?>">
            </div>

            <input type="hidden" name="usuario_id" value="<?= $_SESSION['usuario_id'] ?>">

            <button type="submit" class="btn btn-primary">Cadastrar</button>
            <a href="../view/dashboard.php" class="btn btn-secondary">Voltar</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>