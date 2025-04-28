<?php
define('BASE_PATH', realpath(dirname(__DIR__)));

require_once BASE_PATH . '/dao/produto_dao.php';
require_once BASE_PATH . '/model/produto.php';
require_once BASE_PATH . '/config/database.php';

// Iniciar sessão para obter usuario_id
session_start();
if (!isset($_SESSION['usuario_id'])) {
    error_log(date('[Y-m-d H:i:s] ') . "Erro: Usuário não está logado" . PHP_EOL);
    header("Location: ../view/login.php?erro=nao_logado");
    exit;
}
$usuario_id = (int)$_SESSION['usuario_id'];
error_log(date('[Y-m-d H:i:s] ') . "Usuário logado: usuario_id=$usuario_id" . PHP_EOL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
    $descricao = trim(htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'));
    $fornecedor = trim(htmlspecialchars($_POST['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'));
    $estoque = isset($_POST['estoque']) ? (int)$_POST['estoque'] : 0;
    
    error_log(date('[Y-m-d H:i:s] ') . "Dados recebidos: nome=$nome, fornecedor=$fornecedor, estoque=$estoque, descricao=$descricao, usuario_id=$usuario_id" . PHP_EOL);

    $foto = null;

    $camposObrigatorios = [
        'nome' => $nome,
        'fornecedor' => $fornecedor,
        'estoque' => $estoque
    ];

    foreach ($camposObrigatorios as $campo => $valor) {
        if (empty($valor) && $valor !== '0') {
            error_log(date('[Y-m-d H:i:s] ') . "Erro: Campo obrigatório '$campo' está vazio" . PHP_EOL);
            header("Location: ../view/cadastro_produto.php?erro=campos_obrigatorios&campo=" . urlencode($campo));
            exit;
        }
    }

    if (!is_numeric($estoque) || $estoque < 0) {
        error_log(date('[Y-m-d H:i:s] ') . "Erro: Estoque inválido (valor: $estoque)" . PHP_EOL);
        header("Location: ../view/cadastro_produto.php?erro=estoque_invalido");
        exit;
    }

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['foto'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 16 * 1024 * 1024; // 16MB

        error_log(date('[Y-m-d H:i:s] ') . "Arquivo recebido: nome={$file['name']}, tipo={$file['type']}, tamanho={$file['size']}, erro={$file['error']}" . PHP_EOL);

        if (!in_array($file['type'], $allowedTypes) || $file['size'] > $maxSize || $file['error'] !== UPLOAD_ERR_OK) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro: Foto inválida (tipo={$file['type']}, tamanho={$file['size']}, erro={$file['error']})" . PHP_EOL);
            header("Location: ../view/cadastro_produto.php?erro=foto_invalida");
            exit;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nomeArquivo = uniqid('produto_') . '.' . strtolower($ext);
        $uploadDir = BASE_PATH . '/public/uploads/imagens/';

        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true)) {
                error_log(date('[Y-m-d H:i:s] ') . "Erro: Falha ao criar diretório de upload ($uploadDir)" . PHP_EOL);
                header("Location: ../view/cadastro_produto.php?erro=erro_diretorio");
                exit;
            }
        }

        $caminhoDestino = $uploadDir . $nomeArquivo;
        $foto = '../public/uploads/imagens/' . $nomeArquivo; // Caminho relativo para o banco

        if (!move_uploaded_file($file['tmp_name'], $caminhoDestino)) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro: Falha ao mover arquivo para $caminhoDestino" . PHP_EOL);
            header("Location: ../view/cadastro_produto.php?erro=erro_sistema");
            exit;
        }

        error_log(date('[Y-m-d H:i:s] ') . "Arquivo movido com sucesso para $caminhoDestino" . PHP_EOL);
    }

    try {
        $produto = new Produto($nome, $descricao, $foto, $fornecedor, $usuario_id);
        $produto->setEstoque($estoque);

        $pdo = Database::getConnection();
        if (!$pdo) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro: Falha na conexão com o banco de dados" . PHP_EOL);
            throw new Exception("Falha na conexão com o banco de dados");
        }

        $produtoDao = new ProdutoDAO($pdo);

        error_log(date('[Y-m-d H:i:s] ') . "Verificando se o nome '$nome' já existe" . PHP_EOL);
        if ($produtoDao->nomeExiste($nome)) {
            if ($foto && file_exists(BASE_PATH . '/public' . $foto)) {
                unlink(BASE_PATH . '/public' . $foto);
                error_log(date('[Y-m-d H:i:s] ') . "Arquivo $foto removido devido a nome existente" . PHP_EOL);
            }
            error_log(date('[Y-m-d H:i:s] ') . "Erro: Nome '$nome' já existe" . PHP_EOL);
            header("Location: ../view/cadastro_produto.php?erro=nome_existente");
            exit;
        }

        error_log(date('[Y-m-d H:i:s] ') . "Cadastrando produto no banco de dados" . PHP_EOL);
        $resultado = $produtoDao->cadastrarProduto($produto);

        if ($resultado) {
            error_log(date('[Y-m-d H:i:s] ') . "Produto cadastrado com sucesso: nome=$nome" . PHP_EOL);
            header('Location: ../view/cadastro_produto.php?sucesso=1');
            exit;
        } else {
            if ($foto && file_exists(BASE_PATH . '/public' . $foto)) {
                unlink(BASE_PATH . '/public' . $foto);
                error_log(date('[Y-m-d H:i:s] ') . "Arquivo $foto removido devido a falha no cadastro" . PHP_EOL);
            }
            error_log(date('[Y-m-d H:i:s] ') . "Erro: Falha ao cadastrar produto (resultado falso)" . PHP_EOL);
            throw new Exception("Não foi possível completar o cadastro do produto.");
        }
    } catch (Exception $e) {
        if ($foto && file_exists(BASE_PATH . '/public' . $foto)) {
            unlink(BASE_PATH . '/public' . $foto);
            error_log(date('[Y-m-d H:i:s] ') . "Arquivo $foto removido devido a exceção" . PHP_EOL);
        }
        error_log(date('[Y-m-d H:i:s] ') . "Erro no cadastro de produto: " . $e->getMessage() . " | Stacktrace: " . $e->getTraceAsString() . PHP_EOL);
        header('Location: ../view/cadastro_produto.php?erro=erro_sistema');
        exit;
    }
} else {
    error_log(date('[Y-m-d H:i:s] ') . "Erro: Método de requisição inválido (não é POST)" . PHP_EOL);
    header('Location: ../view/cadastro_produto.php');
    exit;
}
?>