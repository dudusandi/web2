<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ./view/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../model/produto.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);

    // Verificar ID do produto
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        header('Location: ./listar_produtos.php?mensagem=ID inválido&tipo_mensagem=erro');
        exit;
    }

    $produto = $produtoDao->buscarPorId($id);
    if (!$produto) {
        header('Location: ./listar_produtos.php?mensagem=Produto não encontrado&tipo_mensagem=erro');
        exit;
    }

    $mensagem = '';
    $tipoMensagem = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Coletar e sanitizar dados
        $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
        $descricao = trim(htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'));
        $fornecedor = trim(htmlspecialchars($_POST['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'));
        $estoque = trim(filter_input(INPUT_POST, 'estoque', FILTER_SANITIZE_NUMBER_INT));
        $foto = $produto->getFoto();

        // Validar campos obrigatórios
        if (empty($nome) || empty($fornecedor) || !is_numeric($estoque) || (int)$estoque < 0) {
            $mensagem = 'Preencha todos os campos obrigatórios e forneça um estoque válido.';
            $tipoMensagem = 'erro';
        } elseif ($produtoDao->nomeExiste($nome, $id)) {
            $mensagem = 'Nome do produto já existe.';
            $tipoMensagem = 'erro';
        } else {
            // Processar nova foto, se fornecida
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['foto'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 16 * 1024 * 1024;

                if (!in_array($file['type'], $allowedTypes) || $file['size'] > $maxSize || $file['error'] !== UPLOAD_ERR_OK) {
                    $mensagem = 'Foto inválida. Use JPEG, PNG ou GIF (máx. 16MB).';
                    $tipoMensagem = 'erro';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $nomeArquivo = uniqid('produto_') . '.' . strtolower($ext);
                    $caminhoDestino = '../public/uploads/imagens/' . $nomeArquivo;

                    if (move_uploaded_file($file['tmp_name'], $caminhoDestino)) {
                        // Remover foto antiga, se existir
                        if ($foto && file_exists('../public' . $foto)) {
                            unlink('../public' . $foto);
                            error_log("Foto antiga removida: ../public$foto");
                        }
                        $foto = '/public/uploads/imagens/' . $nomeArquivo;
                        error_log("Nova foto salva: $foto");
                    } else {
                        $mensagem = 'Erro ao salvar a foto.';
                        $tipoMensagem = 'erro';
                    }
                }
            }

            if (!$mensagem) {
                // Atualizar produto
                $produtoAtualizado = new Produto($nome, $descricao, $foto, $fornecedor);
                $produtoAtualizado->setEstoque((int)$estoque);
                $produtoAtualizado->setId($id);

                if ($produtoDao->atualizarProduto($produtoAtualizado, $id)) {
                    header('Location: ./listar_produtos.php?mensagem=Produto atualizado com sucesso&tipo_mensagem=sucesso');
                    exit;
                } else {
                    $mensagem = 'Erro ao atualizar o produto.';
                    $tipoMensagem = 'erro';
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Erro em editar_produto.php: " . $e->getMessage());
    $mensagem = 'Erro ao processar: ' . $e->getMessage();
    $tipoMensagem = 'erro';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container py-4">
        <a href="./listar_produtos.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <h1 class="mb-4">Editar Produto</h1>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome *</label>
                <input type="text" class="form-control" id="nome" name="nome" 
                       value="<?= htmlspecialchars($produto->getNome(), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="4"><?= htmlspecialchars($produto->getDescricao() ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="mb-3">
                <label for="fornecedor" class="form-label">Fornecedor *</label>
                <input type="text" class="form-control" id="fornecedor" name="fornecedor" 
                       value="<?= htmlspecialchars($produto->getFornecedor(), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="mb-3">
                <label for="estoque" class="form-label">Estoque *</label>
                <input type="number" class="form-control" id="estoque" name="estoque" 
                       value="<?= $produto->getEstoque() ?>" min="0" required>
            </div>
            <div class="mb-3">
                <label for="foto" class="form-label">Foto</label>
                <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg,image/png,image/gif">
                <?php if ($produto->getFoto()): ?>
                    <img src="<?= htmlspecialchars($produto->getFoto(), ENT_QUOTES, 'UTF-8') ?>" 
                         alt="Foto atual" class="mt-2" style="max-width: 100px;">
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Salvar
            </button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>