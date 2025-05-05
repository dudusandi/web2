<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ./login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/fornecedor_dao.php'; 
require_once '../model/fornecedor.php';
require_once '../model/produto.php';

// Inicialização de variáveis para mensagens
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
    <title>Cadastro de Produto - UcsExpress</title>
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
        <?php if ($sucesso): ?>
            <div class="mensagem sucesso">
                Produto cadastrado com sucesso!
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="mensagem erro">
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

        <!-- Formulário -->
        <div class="form-section">
            <h2>Cadastro de Produto</h2>

            <form action="../controllers/cadastrar_produto.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="usuario_id" value="<?= $_SESSION['usuario_id'] ?>">

                <div class="form-group">
                    <label for="nome">Nome do Produto *</label>
                    <input type="text" id="nome" name="nome" value="<?= $nome ?>" required>
                    <?php if ($erro === 'campos_obrigatorios' && $campo === 'nome'): ?>
                        <div class="error-message">Campo obrigatório</div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="4"><?= $descricao ?></textarea>
                </div>

                <div class="form-group">
                    <label for="foto">Foto do Produto</label>
                    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/gif">
                    <?php if ($erro === 'foto_invalida'): ?>
                        <div class="error-message">A foto deve ser uma imagem válida (JPEG, PNG, GIF) e menor que 2MB.</div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="fornecedor_id">Fornecedor *</label>
                    <?php
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
                    <?php if ($erro === 'campos_obrigatorios' && $campo === 'fornecedor_id'): ?>
                        <div class="error-message">Campo obrigatório</div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="quantidade">Quantidade em Estoque *</label>
                        <input type="number" id="quantidade" name="quantidade" value="<?= $quantidade ?>" required min="0">
                        <?php if ($erro === 'campos_obrigatorios' && $campo === 'quantidade'): ?>
                            <div class="error-message">Campo obrigatório</div>
                        <?php endif; ?>
                        <?php if ($erro === 'estoque_invalido'): ?>
                            <div class="error-message">A quantidade deve ser um número não negativo.</div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="preco">Preço Unitário (R$) *</label>
                        <input type="number" step="0.01" id="preco" name="preco" value="<?= $preco ?>" required min="0">
                        <?php if ($erro === 'campos_obrigatorios' && $campo === 'preco'): ?>
                            <div class="error-message">Campo obrigatório</div>
                        <?php endif; ?>
                        <?php if ($erro === 'estoque_invalido'): ?>
                            <div class="error-message">O preço deve ser um número não negativo.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit">Cadastrar</button>
                <div class="mt-3">
                    <a href="../view/dashboard.php" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>