<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once '../model/produto.php';
require_once '../model/endereco.php';
require_once '../model/fornecedor.php';
require_once '../model/estoque.php';
require_once '../dao/produto_dao.php';
require_once '../config/database.php';

$controller = new ProdutoDAO(Database::getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $fornecedorId = (int)($_POST['fornecedor_id'] ?? 0);
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $preco = floatval($_POST['preco'] ?? 0.00);
    $usuarioId = (int)($_SESSION['usuario_id']);
    $foto = $_FILES['foto'] ?? null;

    // Validações
    if (empty($nome) || $fornecedorId <= 0 || $quantidade < 0 || $preco < 0) {
        $campo = empty($nome) ? 'nome' : ($fornecedorId <= 0 ? 'fornecedor' : 'estoque');
        $erro = 'campos_obrigatorios';
        header("Location: ../view/cadastro_produto.php?erro=$erro&campo=$campo");
        exit;
    }

    // Criação do fornecedor (placeholder, deve ser buscado no banco)
    $fornecedor = new Fornecedor("Nome Temporário", "", "", "", new Endereco("", "", "", "", "", "", ""));
    $fornecedor->setId($fornecedorId);

    // Processamento da foto (simplificado)
    $fotoNome = null;
    if ($foto && $foto['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $fotoNome = uniqid() . '.' . $extensao;
        move_uploaded_file($foto['tmp_name'], '../public/uploads/imagens/' . $fotoNome);
    }

    // Criação do produto
    $produto = new Produto($nome, $descricao, $fotoNome ?? '', $fornecedor, $usuarioId);
    try {
        $produtoDAO->inserir($produto); // Estoque será criado com valores iniciais no DAO
        header('Location: ../view/cadastro_produto.php?sucesso=1');
        exit;
    } catch (Exception $e) {
        header("Location: ../view/cadastro_produto.php?erro=erro_sistema");
        exit;
    }
}
?>