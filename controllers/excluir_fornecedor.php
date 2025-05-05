<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/fornecedor_dao.php';

try {
    $fornecedorDAO = new FornecedorDAO(Database::getConnection());
    $id = $_GET['id'] ?? null;

    if (!$id) {
        throw new Exception('ID não fornecido');
    }

    $fornecedor = $fornecedorDAO->buscarPorId($id);
    if (!$fornecedor) {
        throw new Exception('Fornecedor não encontrado');
    }

    // Verifica se o fornecedor está associado a algum produto
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE fornecedor_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        throw new Exception('Não é possível excluir o fornecedor porque ele está associado a produtos');
    }

    // Exclui o fornecedor
    $fornecedorDAO->excluir($id);

    header('Location: ../view/cadastro_fornecedor.php?mensagem=Fornecedor excluído com sucesso&tipo_mensagem=success');
    exit;
} catch (Exception $e) {
    error_log(date('[Y-m-d H:i:s] ') . "Erro ao excluir fornecedor: " . $e->getMessage() . PHP_EOL);
    header('Location: ../view/cadastro_fornecedor.php?mensagem=Erro ao excluir: ' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
    exit;
}
?>