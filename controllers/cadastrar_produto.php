<?php
$basePath = realpath(dirname(__DIR__));
require_once "$basePath/dao/produto_dao.php";
require_once "$basePath/model/produto.php";
require_once "$basePath/config/database.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coletar e sanitizar dados do formulário
    $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
    $descricao = trim(htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'));
    $fornecedor = trim(htmlspecialchars($_POST['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'));
    $estoque = trim(filter_input(INPUT_POST, 'estoque', FILTER_SANITIZE_NUMBER_INT));
    $foto = null;

    // Validar campos obrigatórios
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

    // Validar estoque
    if (!is_numeric($estoque) || $estoque < 0) {
        header("Location: ../view/cadastro_produto.php?erro=estoque_invalido");
        exit;
    }

    // Processar upload da foto, se fornecida
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['foto'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        // Validar tipo e tamanho
        if (!in_array($file['type'], $allowedTypes) || $file['size'] > $maxSize || $file['error'] !== UPLOAD_ERR_OK) {
            header("Location: ../view/cadastro_produto.php?erro=foto_invalida");
            exit;
        }

        // Gerar nome único para o arquivo
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nomeArquivo = uniqid('produto_') . '.' . strtolower($ext);
        $caminhoDestino = $basePath . '/public/uploads/imagens/' . $nomeArquivo;

        // Mover o arquivo para o destino
        if (!move_uploaded_file($file['tmp_name'], $caminhoDestino)) {
            header("Location: ../view/cadastro_produto.php?erro=erro_sistema");
            exit;
        }

        $foto = '/uploads/imagens/' . $nomeArquivo;
    }

    try {
        // Criar objeto Produto
        $produto = new Produto($nome, $descricao, $foto, $fornecedor);
        $produto->setEstoque($estoque);

        // Conectar ao banco e instanciar ProdutoDAO
        $pdo = Database::getConnection();
        $produtoDao = new ProdutoDAO($pdo);

        // Verificar se o nome do produto já existe
        if ($produtoDao->nomeExiste($nome)) {
            // Remover a foto se foi enviada
            if ($foto && file_exists($basePath . '/public' . $foto)) {
                unlink($basePath . '/public' . $foto);
            }
            header("Location: ../view/cadastro_produto.php?erro=nome_existente");
            exit;
        }

        // Cadastrar produto
        $resultado = $produtoDao->cadastrarProduto($produto);

        if ($resultado) {
            header('Location: ../view/cadastro_produto.php?sucesso=1');
            exit;
        } else {
            // Remover a foto se foi enviada
            if ($foto && file_exists($basePath . '/public' . $foto)) {
                unlink($basePath . '/public' . $foto);
            }
            throw new Exception("Não foi possível completar o cadastro do produto.");
        }
    } catch (Exception $e) {
        // Remover a foto se foi enviada
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