<?php
header('Content-Type: application/json');

error_log("DEBUG: atualizar_produto.php INICIADO");
error_log("DEBUG: _POST data: " . print_r($_POST, true));
error_log("DEBUG: _FILES data: " . print_r($_FILES, true));

session_start();
if (!isset($_SESSION['usuario_id'])) {
    error_log("ERRO: Usuário não autenticado em atualizar_produto.php");
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}
$usuario_id = (int)$_SESSION['usuario_id'];

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../dao/estoque_dao.php';
require_once __DIR__ . '/../model/produto.php';
require_once __DIR__ . '/../model/estoque.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    $estoqueDao = new EstoqueDAO($pdo);

    error_log("DEBUG: DAOs instanciados");

    // Verificar ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        error_log("ERRO: ID inválido: " . $id);
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    // Buscar Produtos
    $produtoExistente = $produtoDao->buscarPorId($id);
    if (!$produtoExistente) {
        error_log("ERRO: Produto não encontrado com ID: " . $id);
        echo json_encode(['error' => 'Produto não encontrado']);
        exit;
    }
    error_log("DEBUG: Produto existente encontrado: " . print_r($produtoExistente, true));

    // Dados do formulário
    $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
    $descricao = trim(htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'));
    $fornecedor = trim(htmlspecialchars($_POST['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'));
    $estoque = (int)($_POST['estoque'] ?? 0);
    $preco = (float)($_POST['preco'] ?? 0);

    // Validações
    if (empty($nome) || empty($fornecedor)) {
        echo json_encode(['error' => 'Nome e fornecedor são obrigatórios']);
        exit;
    }
    if ($estoque < 0) {
        echo json_encode(['error' => 'Estoque inválido']);
        exit;
    }
    if ($preco < 0) {
        echo json_encode(['error' => 'Preço inválido']);
        exit;
    }
    if ($produtoDao->nomeExiste($nome, $id)) {
        error_log("ERRO: Nome do produto já existe: " . $nome);
        echo json_encode(['error' => 'Nome do produto já existe']);
        exit;
    }
    error_log("DEBUG: Validações básicas OK");

    // Obtém a foto atual do produto existente
    $foto = $produtoExistente->getFoto() ?? null;

    // Salvar Foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        
        // Validar tipo de arquivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['error' => 'Foto inválida. Use JPEG, PNG ou GIF (máx. 16MB)']);
            exit;
        }

        // Validar tamanho
        $maxSize = 16 * 1024 * 1024; // 16MB
        if ($file['size'] > $maxSize) {
            echo json_encode(['error' => 'Foto muito grande. Tamanho máximo: 16MB']);
            exit;
        }

        // Ler o conteúdo do arquivo e converter para bytea
        $foto = file_get_contents($file['tmp_name']);
        error_log("DEBUG: Nova foto processada. Tamanho: " . strlen($foto) . " bytes");
    }

    // Novo Produto
    error_log("DEBUG: Criando objeto ProdutoAtualizado com: nome=$nome, descricao=$descricao, fornecedor_id=$fornecedor, usuario_id=$usuario_id");
    $produtoAtualizado = new Produto($nome, $descricao, $foto, (int)$fornecedor, $usuario_id);
    $produtoAtualizado->setId($id);
    $produtoAtualizado->setEstoqueId($produtoExistente->getEstoqueId());
    $produtoAtualizado->setQuantidade($estoque);
    $produtoAtualizado->setPreco($preco);
    error_log("DEBUG: Objeto ProdutoAtualizado criado: " . print_r($produtoAtualizado, true));

    // Atualiza produto
    if ($produtoDao->atualizarProduto($produtoAtualizado)) {
        error_log("DEBUG: produtoDao->atualizarProduto SUCESSO");
        $estoqueId = $produtoExistente->getEstoqueId();
        
        // Criar objeto Estoque com os novos dados de quantidade e preço
        $estoqueObjeto = new Estoque($estoque, $preco); // $estoque e $preco vêm do formulário
        $estoqueObjeto->setId($estoqueId);

        if ($estoqueDao->atualizar($estoqueObjeto)) { // Usar o método atualizar que lida com preço e quantidade
            error_log("DEBUG: estoqueDao->atualizar SUCESSO para estoqueId: $estoqueId, quantidade: $estoque, preco: $preco");
            echo json_encode(['success' => true]);
        } else {
            error_log("ERRO: estoqueDao->atualizar FALHOU para estoqueId: $estoqueId, quantidade: $estoque, preco: $preco");
            echo json_encode(['error' => 'Erro ao atualizar o estoque e preço']);
        }
    } else {
        error_log("ERRO: produtoDao->atualizarProduto FALHOU");
        echo json_encode(['error' => 'Erro ao atualizar o produto']);
    }
} catch (Exception $e) {
    error_log(date('[Y-m-d H:i:s] ') . "EXCEÇÃO em atualizar_produto.php: " . $e->getMessage() . "\nStack: " . $e->getTraceAsString());
    echo json_encode(['error' => 'Erro ao processar: ' . $e->getMessage()]);
}
?>