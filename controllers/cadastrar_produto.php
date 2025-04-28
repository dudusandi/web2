<?php
define('BASE_PATH', realpath(dirname(__DIR__)));

require_once BASE_PATH . '/dao/produto_dao.php';
require_once BASE_PATH . '/model/produto.php';
require_once BASE_PATH . '/config/database.php';

session_start();

function logError(string $msg): void {
    error_log(date('[Y-m-d H:i:s] ') . $msg . PHP_EOL);
}

function redirectWithError(string $error, string $campo = ''): void {
    $url = "../view/cadastro_produto.php?erro=$error";
    if ($campo) $url .= "&campo=" . urlencode($campo);
    header("Location: $url");
    exit;
}

// Verifica usuário logado
if (empty($_SESSION['usuario_id'])) {
    logError("Erro: Usuário não está logado");
    header("Location: ../view/login.php?erro=nao_logado");
    exit;
}
$usuario_id = (int)$_SESSION['usuario_id'];
logError("Usuário logado: usuario_id=$usuario_id");

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logError("Erro: Método de requisição inválido (não é POST)");
    header('Location: ../view/cadastro_produto.php');
    exit;
}

// Sanitiza e obtém dados do POST
$nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
$descricao = trim(htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'));
$fornecedor = trim(htmlspecialchars($_POST['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'));
$estoque = filter_var($_POST['estoque'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

logError("Dados recebidos: nome=$nome, fornecedor=$fornecedor, estoque=$estoque, descricao=$descricao, usuario_id=$usuario_id");

// Validação campos obrigatórios
foreach (['nome' => $nome, 'fornecedor' => $fornecedor] as $campo => $valor) {
    if ($valor === '') {
        logError("Erro: Campo obrigatório '$campo' está vazio");
        redirectWithError('campos_obrigatorios', $campo);
    }
}
if ($estoque === false || $estoque === null) {
    logError("Erro: Estoque inválido (valor: {$_POST['estoque']})");
    redirectWithError('estoque_invalido');
}

// Tratamento do upload da foto
$foto = null;
if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['foto'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 16 * 1024 * 1024; // 16MB

    logError("Arquivo recebido: nome={$file['name']}, tipo={$file['type']}, tamanho={$file['size']}, erro={$file['error']}");

    if (!in_array($file['type'], $allowedTypes) || $file['size'] > $maxSize || $file['error'] !== UPLOAD_ERR_OK) {
        logError("Erro: Foto inválida (tipo={$file['type']}, tamanho={$file['size']}, erro={$file['error']})");
        redirectWithError('foto_invalida');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nomeArquivo = uniqid('produto_') . '.' . $ext;
    $uploadDir = BASE_PATH . '/public/uploads/imagens/';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        logError("Erro: Falha ao criar diretório de upload ($uploadDir)");
        redirectWithError('erro_diretorio');
    }

    $caminhoDestino = $uploadDir . $nomeArquivo;
    $foto = '../public/uploads/imagens/' . $nomeArquivo;

    if (!move_uploaded_file($file['tmp_name'], $caminhoDestino)) {
        logError("Erro: Falha ao mover arquivo para $caminhoDestino");
        redirectWithError('erro_sistema');
    }

    logError("Arquivo movido com sucesso para $caminhoDestino");
}

try {
    $produto = new Produto($nome, $descricao, $foto, $fornecedor, $usuario_id);
    $produto->setEstoque($estoque);

    $pdo = Database::getConnection();
    if (!$pdo) throw new Exception("Falha na conexão com o banco de dados");

    $produtoDao = new ProdutoDAO($pdo);

    logError("Verificando se o nome '$nome' já existe");
    if ($produtoDao->nomeExiste($nome)) {
        if ($foto && file_exists(BASE_PATH . '/public' . $foto)) {
            unlink(BASE_PATH . '/public' . $foto);
            logError("Arquivo $foto removido devido a nome existente");
        }
        redirectWithError('nome_existente');
    }

    logError("Cadastrando produto no banco de dados");
    if ($produtoDao->cadastrarProduto($produto)) {
        logError("Produto cadastrado com sucesso: nome=$nome");
        header('Location: ../view/cadastro_produto.php?sucesso=1');
        exit;
    }

    // Caso cadastro falhe
    if ($foto && file_exists(BASE_PATH . '/public' . $foto)) {
        unlink(BASE_PATH . '/public' . $foto);
        logError("Arquivo $foto removido devido a falha no cadastro");
    }
    throw new Exception("Não foi possível completar o cadastro do produto.");

} catch (Exception $e) {
    if ($foto && file_exists(BASE_PATH . '/public' . $foto)) {
        unlink(BASE_PATH . '/public' . $foto);
        logError("Arquivo $foto removido devido a exceção");
    }
    logError("Erro no cadastro de produto: " . $e->getMessage() . " | Stacktrace: " . $e->getTraceAsString());
    redirectWithError('erro_sistema');
}
?>
