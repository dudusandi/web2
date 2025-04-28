<?php
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}
$usuario_id = (int)$_SESSION['usuario_id'];

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../model/produto.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);

    // Verificar ID do produto
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    $produto = $produtoDao->buscarPorId($id);
    if (!$produto) {
        echo json_encode(['error' => 'Produto não encontrado']);
        exit;
    }

    // Coletar e sanitizar dados
    $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
    $descricao = trim(htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'));
    $fornecedor = trim(htmlspecialchars($_POST['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'));
    $estoque = (int)($_POST['estoque'] ?? 0);
    $foto = $produto->getFoto();

    // Validar campos
    if (empty($nome) || empty($fornecedor)) {
        echo json_encode(['error' => 'Nome e fornecedor são obrigatórios']);
        exit;
    }
    if ($estoque < 0) {
        echo json_encode(['error' => 'Estoque inválido']);
        exit;
    }
    if ($produtoDao->nomeExiste($nome, $id)) {
        echo json_encode(['error' => 'Nome do produto já existe']);
        exit;
    }

    // Processar nova foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 16 * 1024 * 1024;

        if (!in_array($file['type'], $allowedTypes) || $file['size'] > $maxSize) {
            echo json_encode(['error' => 'Foto inválida. Use JPEG, PNG ou GIF (máx. 16MB)']);
            exit;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nomeArquivo = uniqid('produto_') . '.' . strtolower($ext);
        $caminhoDestino = __DIR__ . '/../public/uploads/imagens/' . $nomeArquivo;

        if (move_uploaded_file($file['tmp_name'], $caminhoDestino)) {
            if ($foto && file_exists(__DIR__ . '/../public' . $foto)) {
                unlink(__DIR__ . '/../public' . $foto);
                error_log(date('[Y-m-d H:i:s] ') . "Foto antiga removida: ../public$foto" . PHP_EOL);
            }
            $foto = '../public/uploads/imagens/' . $nomeArquivo;
            error_log(date('[Y-m-d H:i:s] ') . "Nova foto salva: $foto" . PHP_EOL);
        } else {
            echo json_encode(['error' => 'Erro ao salvar a foto']);
            exit;
        }
    }

    // Atualizar produto
    $produtoAtualizado = new Produto($nome, $descricao, $foto, $fornecedor, $usuario_id);
    $produtoAtualizado->setEstoque($estoque);
    $produtoAtualizado->setId($id);

    if ($produtoDao->atualizarProduto($produtoAtualizado, $id)) {
        echo json_encode(['success' => true, 'foto' => $foto]);
    } else {
        echo json_encode(['error' => 'Erro ao atualizar o produto']);
    }
} catch (Exception $e) {
    error_log(date('[Y-m-d H:i:s] ') . "Erro em atualizar_produto.php: " . $e->getMessage() . PHP_EOL);
    echo json_encode(['error' => 'Erro ao processar: ' . $e->getMessage()]);
}
?>