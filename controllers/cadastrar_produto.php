<?php
// Limpa qualquer saída anterior
ob_start();
ob_clean();

session_start();
require_once '../config/database.php';
require_once '../dao/produto_dao.php';
require_once '../model/produto.php';

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Validar dados recebidos
    if (!isset($_POST['nome']) || empty($_POST['nome'])) {
        throw new Exception('Nome do produto é obrigatório');
    }

    if (!isset($_POST['fornecedor_id']) || empty($_POST['fornecedor_id'])) {
        throw new Exception('Fornecedor é obrigatório');
    }

    if (!isset($_POST['quantidade']) || !is_numeric($_POST['quantidade']) || $_POST['quantidade'] < 0) {
        throw new Exception('Quantidade inválida');
    }

    if (!isset($_POST['preco']) || !is_numeric($_POST['preco']) || $_POST['preco'] < 0) {
        throw new Exception('Preço inválido');
    }

    // Processar upload da foto
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['foto']['type'], $allowedTypes)) {
            throw new Exception('Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.');
        }

        if ($_FILES['foto']['size'] > $maxSize) {
            throw new Exception('Arquivo muito grande. Tamanho máximo: 2MB');
        }

        // Ler o conteúdo do arquivo e converter para bytea
        $foto = file_get_contents($_FILES['foto']['tmp_name']);
    }

    // Criar objeto Produto
    $produto = new Produto(
        $_POST['nome'],
        $_POST['descricao'] ?? null,
        $foto,
        $_POST['fornecedor_id'],
        $_SESSION['usuario_id']
    );

    // Definir quantidade e preço
    $produto->setQuantidade($_POST['quantidade']);
    $produto->setPreco($_POST['preco']);

    // Inserir no banco de dados
    $pdo = Database::getConnection();
    $produtoDAO = new ProdutoDAO($pdo);
    $produtoDAO->cadastrarProduto($produto, $_POST['quantidade'], $_POST['preco']);

    echo json_encode([
        'success' => true,
        'message' => 'Produto cadastrado com sucesso'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>