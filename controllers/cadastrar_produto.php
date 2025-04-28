<?php
define('BASE_PATH', realpath(dirname(__DIR__)));

require_once BASE_PATH . '/dao/produto_dao.php';
require_once BASE_PATH . '/model/produto.php';
require_once BASE_PATH . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
    $descricao = trim(htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'));
    $fornecedor = trim(htmlspecialchars($_POST['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'));
    $estoque = isset($_POST['estoque']) ? (int)$_POST['estoque'] : 0;
    
    $foto = null;

    $camposObrigatorios = [
        'nome' => $nome,
        'fornecedor' => $fornecedor,
        'estoque' => $estoque
    ];

    foreach ($camposObrigatorios as $campo => $valor) {
        if (empty($valor) && $valor !== '0') {
            header("Location: ../view/cadastro_produto.php?erro=campos_obrigatorios&campo=" . urlencode($campo));
            exit;
        }
    }

    if (!is_numeric($estoque) || $estoque < 0) {
        header("Location: ../view/cadastro_produto.php?erro=estoque_invalido");
        exit;
    }

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['foto'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 16 * 1024 * 1024; // 16MB

        if (!in_array($file['type'], $allowedTypes) || $file['size'] > $maxSize || $file['error'] !== UPLOAD_ERR_OK) {
            header("Location: ../view/cadastro_produto.php?erro=foto_invalida");
            exit;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nomeArquivo = uniqid('produto_') . '.' . strtolower($ext);
        $uploadDir = BASE_PATH . '/public/uploads/imagens/';

// Verifica se o diretório existe, se não, cria
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$caminhoDestino = $uploadDir . $nomeArquivo;
$foto = '/public/uploads/imagens/' . $nomeArquivo; // Caminho relativo para o banco

        if (!move_uploaded_file($file['tmp_name'], $caminhoDestino)) {
            header("Location: ../view/cadastro_produto.php?erro=erro_sistema");
            exit;
        }

        $foto = '../public/uploads/imagens/' . $nomeArquivo;
    }

    try {
        $produto = new Produto($nome, $descricao, $foto, $fornecedor);
        $produto->setEstoque($estoque);

        $pdo = Database::getConnection();
        $produtoDao = new ProdutoDAO($pdo);

        if ($produtoDao->nomeExiste($nome)) {
            if ($foto && file_exists($basePath . '/public' . $foto)) {
                unlink($basePath . '/public' . $foto);
            }
            header("Location: ../view/cadastro_produto.php?erro=nome_existente");
            exit;
        }

        $resultado = $produtoDao->cadastrarProduto($produto);

        if ($resultado) {
            header('Location: ../view/cadastro_produto.php?sucesso=1');
            exit;
        } else {
            if ($foto && file_exists($basePath . '/public' . $foto)) {
                unlink($basePath . '/public' . $foto);
            }
            throw new Exception("Não foi possível completar o cadastro do produto.");
        }
    } catch (Exception $e) {
        if ($foto && file_exists($basePath . '/public' . $foto)) {
            unlink($basePath . '/public' . $foto);
        }
        error_log("Erro no cadastro de produto: " . $e->getMessage());
        header('Location: ../view/cadastro_produto.php?erro=erro_sistema');
        exit;
    }
} else {
    header('Location: ../view/cadastro_produto.php');
    exit;
}
?>