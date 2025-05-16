<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
// require_once __DIR__ . '/../dao/estoque_dao.php'; // Não é mais necessário aqui diretamente

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    // $estoqueDao = new EstoqueDAO($pdo); // Não é mais necessário aqui diretamente

    $id = $_GET['id'] ?? null;

    if ($id) {
        $pdo->beginTransaction();
        
        // A lógica de buscar o produto, verificar estoqueId e excluir estoque (se necessário)
        // agora está encapsulada em removerProduto().
        // removerProduto() também já deleta o produto.
        if ($produtoDao->removerProduto((int)$id)) {
            $pdo->commit();
            header('Location: ../view/dashboard.php?mensagem=Produto+excluído+com+sucesso&tipo_mensagem=success');
            exit;
        } else {
            // Se removerProduto retornar false (embora atualmente retorne true ou lance exceção),
            // ou se lançar uma exceção que não seja pega pelo catch abaixo e este else for alcançado.
            $pdo->rollBack();
            throw new Exception('Erro ao remover o produto.'); // Mensagem genérica
        }
    } else {
        throw new Exception('ID não fornecido');
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log(date('[Y-m-d H:i:s] ') . "Erro ao excluir produto: " . $e->getMessage() . PHP_EOL);
    header('Location: ../view/dashboard.php?mensagem=Erro+ao+excluir:+' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
    exit;
}
?>