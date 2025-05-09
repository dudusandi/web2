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
require_once '../dao/cliente_dao.php';
require_once '../model/cliente.php';

// Busca todos os clientes (inicialmente)
try {
    $clienteDAO = new ClienteDAO(Database::getConnection());
    $itensPorPagina = 6;
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $offset = ($pagina - 1) * $itensPorPagina;
    
    $clientes = $clienteDAO->listarTodos($itensPorPagina, $offset);
    $totalClientes = $clienteDAO->contarTodos();
    $totalPaginas = ceil($totalClientes / $itensPorPagina);
} catch (Exception $e) {
    $clientes = [];
    $mensagem = "Erro ao listar clientes: " . $e->getMessage();
    $tipoMensagem = 'erro';
}

// Mensagens
$mensagem = $_GET['mensagem'] ?? '';
$tipoMensagem = $_GET['tipo_mensagem'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - UcsExpress</title>
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

        <!-- Botões de Navegação -->
        <div class="d-flex justify-content-between mb-4">
            <h2>Gerenciar Clientes</h2>
            <div>
                <a href="cadastro_cliente.php" class="btn btn-primary me-2">
                    <i class="bi bi-plus"></i> Cadastrar Novo
                </a>
                <a href="dashboard.php" class="btn btn-secondary">Voltar ao Dashboard</a>
            </div>
        </div>

        <!-- Listagem de Clientes -->
        <div id="clientesContainer">
            <?php if (empty($clientes)): ?>
                <div class="empty-state">
                    <i class="bi bi-person" style="font-size: 3rem;"></i>
                    <h3 class="mt-3">Nenhum cliente cadastrado</h3>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($clientes as $cliente): ?>
                        <?php $endereco = $cliente->getEndereco(); ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?= $cliente->getNome() ?></h5>
                                    <p class="card-text text-muted">
                                        <strong>Telefone:</strong> <?= $cliente->getTelefone() ?><br>
                                        <strong>Email:</strong> <?= $cliente->getEmail() ?><br>
                                        <strong>Endereço:</strong> 
                                        <?= $endereco->getRua() . ', ' . $endereco->getNumero() . ', ' . $endereco->getBairro() . ', ' . $endereco->getCidade() . ' - ' . $endereco->getEstado() ?>
                                    </p>
                                </div>
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="editar_cliente.php?id=<?= $cliente->getId() ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <a href="../controllers/excluir_cliente.php?id=<?= $cliente->getId() ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Tem certeza que deseja excluir o cliente <?= $cliente->getNome() ?>?')">
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