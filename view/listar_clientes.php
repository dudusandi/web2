<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/cliente_dao.php';
require_once '../model/cliente.php';

// Busca todos os clientes (inicialmente)
try {
    $clienteDAO = new ClienteDAO(Database::getConnection());
    $itensPorPagina = 6;
    $clientes = $clienteDAO->listarTodos($itensPorPagina, 0);
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
    <style>
        .spinner-container {
            display: none;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Cabeçalho -->
    <div class="header">
        <div class="logo">UCS<span>express</span></div>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Pesquisar clientes..." autocomplete="off">
        </div>
    </div>

    <div class="container">
        <!-- Mensagens -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
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

        <!-- Spinner de Carregamento -->
        <div id="spinner" class="spinner-container">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
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
                                    <h5 class="card-title"><?= htmlspecialchars($cliente->getNome(), ENT_QUOTES, 'UTF-8') ?></h5>
                                    <p class="card-text text-muted">
                                        <strong>Telefone:</strong> <?= htmlspecialchars($cliente->getTelefone(), ENT_QUOTES, 'UTF-8') ?><br>
                                        <strong>Email:</strong> <?= htmlspecialchars($cliente->getEmail(), ENT_QUOTES, 'UTF-8') ?><br>
                                        <strong>Cartão de Crédito:</strong> <?= htmlspecialchars($cliente->getCartaoCredito(), ENT_QUOTES, 'UTF-8') ?><br>
                                        <strong>Endereço:</strong> 
                                        <?= htmlspecialchars($endereco->getRua() . ', ' . $endereco->getNumero() . ', ' . $endereco->getBairro() . ', ' . $endereco->getCidade() . ' - ' . $endereco->getEstado(), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="editar_cliente.php?id=<?= $cliente->getId() ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?= $cliente->getId() ?>, '<?= htmlspecialchars($cliente->getNome(), ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="bi bi-trash"></i> Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <nav id="paginacao" aria-label="Page navigation" class="mt-4">
                    <ul class="pagination">
                        <!-- Paginação será gerada dinamicamente -->
                    </ul>
                </nav>
            <?php endif; ?>
        </div>

        <!-- Modal de Confirmação de Exclusão -->
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmModalLabel">Confirmar Exclusão</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja excluir o cliente "<span id="confirmClienteNome"></span>"?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <a id="btnConfirmarExclusao" href="#" class="btn btn-danger">Excluir</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const itensPorPagina = 6;
        let debounceTimeout = null;

        // Função para carregar clientes
        function carregarClientes(termo = '', pagina = 1) {
            const spinner = document.getElementById('spinner');
            const clientesContainer = document.getElementById('clientesContainer');
            spinner.style.display = 'block';
            clientesContainer.style.display = 'none';

            fetch(`../controllers/buscar_clientes.php?termo=${encodeURIComponent(termo)}&pagina=${pagina}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erro HTTP: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    spinner.style.display = 'none';
                    clientesContainer.style.display = 'block';

                    if (data.error) {
                        clientesContainer.innerHTML = `
                            <div class="empty-state">
                                <i class="bi bi-person" style="font-size: 3rem;"></i>
                                <h3 class="mt-3">Erro: ${data.error}</h3>
                            </div>
                        `;
                        return;
                    }

                    const clientesHtml = data.clientes.map(cliente => `
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">${cliente.nome}</h5>
                                    <p class="card-text text-muted">
                                        <strong>Telefone:</strong> ${cliente.telefone}<br>
                                        <strong>Email:</strong> ${cliente.email}<br>
                                        <strong>Cartão de Crédito:</strong> ${cliente.cartao_credito}<br>
                                        <strong>Endereço:</strong> ${cliente.endereco.rua}, ${cliente.endereco.numero}, ${cliente.endereco.bairro}, ${cliente.endereco.cidade} - ${cliente.endereco.estado}${cliente.endereco.complemento ? ', ' + cliente.endereco.complemento : ''}
                                    </p>
                                </div>
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="editar_cliente.php?id=${cliente.id}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(${cliente.id}, '${cliente.nome}')">
                                        <i class="bi bi-trash"></i> Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('');

                    clientesContainer.innerHTML = `
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            ${clientesHtml}
                        </div>
                        ${data.total > itensPorPagina ? `
                            <nav id="paginacao" aria-label="Page navigation" class="mt-4">
                                <ul class="pagination">
                                    ${pagina > 1 ? `<li class="page-item"><a class="page-link" href="#" onclick="carregarClientes('${termo}', ${pagina - 1}); return false;">Anterior</a></li>` : ''}
                                    ${Array.from({ length: Math.ceil(data.total / itensPorPagina) }, (_, i) => `
                                        <li class="page-item ${i + 1 === pagina ? 'active' : ''}">
                                            <a class="page-link" href="#" onclick="carregarClientes('${termo}', ${i + 1}); return false;">${i + 1}</a>
                                        </li>
                                    `).join('')}
                                    ${pagina < Math.ceil(data.total / itensPorPagina) ? `<li class="page-item"><a class="page-link" href="#" onclick="carregarClientes('${termo}', ${pagina + 1}); return false;">Próxima</a></li>` : ''}
                                </ul>
                            </nav>
                        ` : ''}
                    `;

                    if (data.total === 0 && termo) {
                        clientesContainer.innerHTML = `
                            <div class="empty-state">
                                <i class="bi bi-person" style="font-size: 3rem;"></i>
                                <h3 class="mt-3">Nenhum cliente encontrado para "${termo}"</h3>
                            </div>
                        `;
                    } else if (data.total === 0) {
                        clientesContainer.innerHTML = `
                            <div class="empty-state">
                                <i class="bi bi-person" style="font-size: 3rem;"></i>
                                <h3 class="mt-3">Nenhum cliente cadastrado</h3>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    spinner.style.display = 'none';
                    clientesContainer.style.display = 'block';
                    clientesContainer.innerHTML = `
                        <div class="empty-state">
                            <i class="bi bi-person" style="font-size: 3rem;"></i>
                            <h3 class="mt-3">Erro ao carregar clientes: ${error.message}</h3>
                        </div>
                    `;
                });
        }

        // Função debounce para limitar a frequência das chamadas
        function debounce(func, wait) {
            return function (...args) {
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Evento de busca
        document.getElementById('searchInput').addEventListener('input', debounce(function(e) {
            const termo = e.target.value.trim();
            if (termo.length >= 2 || termo === '') {
                carregarClientes(termo, 1);
            }
        }, 500));

        // Carregar clientes iniciais
        window.onload = function() {
            carregarClientes('', 1);
        };

        function confirmarExclusao(id, nome) {
            document.getElementById('confirmClienteNome').textContent = nome;
            document.getElementById('btnConfirmarExclusao').href = `../controllers/cliente_controller.php?acao=excluir&id=${id}`;
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            confirmModal.show();
        }
    </script>
</body>
</html>