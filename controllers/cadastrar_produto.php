<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once '../model/produto.php';
require_once '../dao/produto_dao.php';
require_once '../config/database.php';

$produtoDAO = new ProdutoDAO(Database::getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $fornecedorId = (int)($_POST['fornecedor_id'] ?? 0);
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $preco = floatval($_POST['preco'] ?? 0.00);
    $usuarioId = (int)($_SESSION['usuario_id']);
    $foto = $_FILES['foto'] ?? null;

    // Log dos dados recebidos
    error_log("Dados recebidos: nome=$nome, fornecedorId=$fornecedorId, quantidade=$quantidade, preco=$preco, usuarioId=$usuarioId");

    // Validações
    if (empty($nome) || $fornecedorId <= 0 || $quantidade < 0 || $preco < 0) {
        $campo = empty($nome) ? 'nome' : ($fornecedorId <= 0 ? 'fornecedor' : 'estoque');
        $erro = empty($nome) || $fornecedorId <= 0 ? 'campos_obrigatorios' : 'estoque_invalido';
        error_log("Validação falhou: campo=$campo, erro=$erro");
        header("Location: ../view/cadastro_produto.php?erro=$erro&campo=$campo");
        exit;
    }

    // Validar se o fornecedor existe para o usuário
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fornecedores WHERE id = :fornecedor_id");
    $stmt->execute([':fornecedor_id' => $fornecedorId]);
    if ($stmt->fetchColumn() == 0) {
        header("Location: ../view/cadastro_produto.php?erro=fornecedor_invalido");
        exit;
    }

    // Processamento da foto
    $fotoNome = null;
    if ($foto && $foto['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 16 * 1024 * 1024; // 16MB
        if (!in_array($foto['type'], $allowedTypes) || $foto['size'] > $maxSize) {
            error_log("Foto inválida: tipo={$foto['type']}, tamanho={$foto['size']}");
            header("Location: ../view/cadastro_produto.php?erro=foto_invalida");
            exit;
        }

        $extensao = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $fotoNome = uniqid() . '.' . $extensao;
        $caminhoDestino = realpath(__DIR__ . '/../public/uploads/imagens/') . '/' . $fotoNome;
        if (!move_uploaded_file($foto['tmp_name'], $caminhoDestino)) {
            error_log("Erro ao mover arquivo: caminho=$caminhoDestino, erro={$foto['error']}");
            header("Location: ../view/cadastro_produto.php?erro=erro_foto");
            exit;
        }
        error_log("Foto salva com sucesso: $fotoNome");
    } else {
        error_log("Nenhum arquivo enviado ou erro no upload: " . ($foto['error'] ?? 'N/A'));
    }

    // Criação do produto
    $produto = new Produto($nome, $descricao, $fotoNome ?? '', $fornecedorId, $usuarioId);
    try {
        error_log("Tentando cadastrar produto: nome=$nome, fornecedorId=$fornecedorId, usuarioId=$usuarioId");
        $produtoDAO->cadastrarProduto($produto, $quantidade, $preco);
        error_log("Produto cadastrado com sucesso");
        header('Location: ../view/cadastro_produto.php?sucesso=1');
        exit;
    } catch (Exception $e) {
        error_log("Erro ao cadastrar produto: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        header("Location: ../view/cadastro_produto.php?erro=erro_sistema&mensagem=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../view/cadastro_produto.php?erro=metodo_invalido");
    exit;
}
?>