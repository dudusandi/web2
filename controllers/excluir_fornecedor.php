<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/fornecedor_dao.php';

try {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        throw new Exception('ID não fornecido');
    }

    $fornecedorDAO = new FornecedorDAO(Database::getConnection());
    
    // A lógica de verificar se o fornecedor existe, se está associado a produtos,
    // e a exclusão do fornecedor e seu endereço (com transação) agora estão em removerFornecedor().
    $fornecedorDAO->removerFornecedor((int)$id);
    
    header('Location: ../view/listar_fornecedor.php?mensagem=Fornecedor excluído com sucesso&tipo_mensagem=success');
    exit;

} catch (Exception $e) {
    // A transação é tratada dentro de removerFornecedor, então não precisamos de rollback aqui.
    header('Location: ../view/listar_fornecedor.php?mensagem=Erro ao excluir: ' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
    exit;
}
?>