<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);

    $termo = $_GET['termo'] ?? '';
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $itensPorPagina = 6; // Mesmo valor usado no frontend

    $offset = ($pagina - 1) * $itensPorPagina;

    // Busca produtos com base no termo
    if ($termo) {
        $produtos = $produtoDao->buscarProdutosDinamicos($termo);
        $total = $produtoDao->contarProdutosBuscados($termo);
    } else {
        $produtos = $produtoDao->listarTodosProdutos();
        $total = $produtoDao->contarProdutos();
    }

    // Aplica paginação manualmente no PHP
    $produtosPaginados = array_slice($produtos, $offset, $itensPorPagina);

    // Prepara os dados para o frontend
    $produtosJson = array_map(function($produto) {
        return [
            'id' => $produto->getId(),
            'nome' => htmlspecialchars($produto->getNome(), ENT_QUOTES, 'UTF-8'),
            'foto' => $produto->getFoto(),
            'quantidade' => $produto->getQuantidade(),
            'preco' => $produto->getPreco(),
            'fornecedor_nome' => htmlspecialchars($produto->fornecedor_nome ?? 'Sem fornecedor', ENT_QUOTES, 'UTF-8'),
            'usuario_id' => $produto->getUsuarioId()
        ];
    }, $produtosPaginados);

    echo json_encode([
        'produtos' => $produtosJson,
        'total' => $total,
        'pagina' => $pagina
    ]);
} catch (Exception $e) {
    error_log("Erro em buscar_produtos.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao buscar produtos']);
}
?>