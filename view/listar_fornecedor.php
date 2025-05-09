<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica se o usuário é administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/fornecedor_dao.php';
require_once '../model/fornecedor.php';

// Busca todos os fornecedores (inicialmente)
try {
    $fornecedorDAO = new FornecedorDAO(Database::getConnection());
    $itensPorPagina = 6;
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $offset = ($pagina - 1) * $itensPorPagina;
    
    $fornecedores = $fornecedorDAO->listarTodos($itensPorPagina, $offset);
    $totalFornecedores = $fornecedorDAO->contarTodos();
    $totalPaginas = ceil($totalFornecedores / $itensPorPagina);
} catch (Exception $e) {
    $fornecedores = [];
    $mensagem = "Erro ao listar fornecedores: " . $e->getMessage();
    $tipoMensagem = 'erro';
}

// Mensagens de feedback
$mensagem = $_GET['mensagem'] ?? '';
$tipoMensagem = $_GET['tipo_mensagem'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Fornecedores - UcsExpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="listar.css">
</head>
<body>
    <!-- Cabeçalho -->
    <div class="header">
        <div class="logo">UCS<span>express</span></div>
    </div>

    <div class="container">
        <!-- Mensagens -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Botão para Adicionar Novo Fornecedor -->
        <div class="d-flex justify-content-between mb-4">
            <h2>Gerenciar Fornecedores</h2>
            <div>
                <a href="cadastro_fornecedor.php" class="btn btn-primary ms-2">
                    <i class="bi bi-plus"></i> Adicionar Fornecedor
                </a>
                <a href="dashboard.php" class="btn btn-secondary ms-2">Voltar ao Dashboard</a>
            </div>
        </div>

        <!-- Listagem de Fornecedores -->
        <div id="fornecedoresContainer">
            <?php if (empty($fornecedores)): ?>
                <div class="empty-state">
                    <i class="bi bi-building" style="font-size: 3rem;"></i>
                    <h3 class="mt-3">Nenhum fornecedor cadastrado</h3>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <?php $endereco = $fornecedor->getEndereco(); ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?= $fornecedor->getNome() ?></h5>
                                    <p class="card-text text-muted">
                                        <strong>Descrição:</strong> <?= $fornecedor->getDescricao() ?? 'Nenhuma' ?><br>
                                        <strong>Telefone:</strong> <?= $fornecedor->getTelefone() ?><br>
                                        <strong>Email:</strong> <?= $fornecedor->getEmail() ?><br>
                                        <strong>Endereço:</strong> 
                                        <?= $endereco->getRua() . ', ' . $endereco->getNumero() . ', ' . $endereco->getBairro() . ', ' . $endereco->getCidade() . ' - ' . $endereco->getEstado() ?>
                                    </p>
                                </div>
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="editar_fornecedor.php?id=<?= $fornecedor->getId() ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <a href="../controllers/excluir_fornecedor.php?id=<?= $fornecedor->getId() ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Tem certeza que deseja excluir o fornecedor <?= $fornecedor->getNome() ?>?')">
                                        <i class="bi bi-trash"></i> Excluir
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginação -->
                <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Navegação de páginas" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>">Anterior</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagina < $totalPaginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>">Próxima</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>