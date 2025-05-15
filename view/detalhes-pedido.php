<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: meus-pedidos.php');
    exit;
}

$pedidoId = (int)$_GET['id'];

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/pedido_dao.php';

try {
    $pdo = Database::getConnection();
    $pedidoDao = new PedidoDAO($pdo);
    
    // Buscar pedido
    $pedido = $pedidoDao->buscarPorId($pedidoId);
    
    // Verificar se o pedido pertence ao cliente
    if (!$pedido || $pedido->getCliente()->getId() != $_SESSION['usuario_id']) {
        header('Location: meus-pedidos.php');
        exit;
    }
    
    // Buscar itens do pedido
    $itensPedido = $pedido->getItensPedido();
    
} catch (Exception $e) {
    error_log("Erro ao buscar detalhes do pedido: " . $e->getMessage());
    $mensagem = "Erro ao carregar detalhes do pedido: " . $e->getMessage();
    $tipoMensagem = 'erro';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido - UcsExpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="header">
        <div class="logo">UCS<span>express</span></div>
        <div class="user-options">
            <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
            <a href="../controllers/logout_controller.php">Sair</a>
            <a href="meus-pedidos.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="container mt-4">
        <h2>Detalhes do Pedido #<?= htmlspecialchars($pedido->getNumero()) ?></h2>
        
        <?php if (isset($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Informações do Pedido</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Número:</strong> <?= htmlspecialchars($pedido->getNumero()) ?></p>
                        <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido->getDataPedido())) ?></p>
                        <p>
                            <strong>Situação:</strong> 
                            <span class="badge 
                                <?php 
                                switch($pedido->getSituacao()) {
                                    case 'NOVO': echo 'bg-primary'; break;
                                    case 'EM_PREPARACAO': echo 'bg-warning'; break;
                                    case 'ENVIADO': echo 'bg-info'; break;
                                    case 'ENTREGUE': echo 'bg-success'; break;
                                    case 'CANCELADO': echo 'bg-danger'; break;
                                    default: echo 'bg-secondary';
                                }
                                ?>">
                                <?= htmlspecialchars($pedido->getSituacao()) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <?php if ($pedido->getDataEntrega()): ?>
                            <p><strong>Data de Entrega:</strong> <?= date('d/m/Y H:i', strtotime($pedido->getDataEntrega())) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Itens do Pedido</h5>
            </div>
            <div class="card-body">
                <?php if (empty($itensPedido)): ?>
                    <p>Nenhum item encontrado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Preço Unitário</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                foreach ($itensPedido as $item): 
                                    $produto = $item->getProduto();
                                    $subtotal = $item->getQuantidade() * $item->getPreco();
                                    $total += $subtotal;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($produto->getNome()) ?></td>
                                        <td><?= $item->getQuantidade() ?></td>
                                        <td>R$ <?= number_format($item->getPreco(), 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td><strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 