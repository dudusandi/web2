<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../model/produto.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);

    // Filtros e paginação
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $itensPorPagina = 6;
    $offset = ($pagina - 1) * $itensPorPagina;

    // Busca produtos com paginação
    error_log("Listando produtos, página: $pagina, offset: $offset");
    $produtos = $produtoDao->listarTodos($itensPorPagina, $offset);
    $totalProdutos = $produtoDao->contarProdutos();

    // Mensagens
    $mensagem = $_GET['mensagem'] ?? '';
    $tipoMensagem = $_GET['tipo_mensagem'] ?? '';
} catch (Exception $e) {
    error_log("Erro ao listar produtos: " . $e->getMessage());
    $mensagem = "Erro ao carregar produtos: " . $e->getMessage();
    $tipoMensagem = 'erro';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card-img-top {
            height: 200px;
            object-fit: contain;
            background-color: #f8f9fa;
        }
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        .pagination {
            justify-content: center;
        }
        .modal-body .form-control {
            margin-bottom: 1rem;
        }
        #mensagemErro {
            display: none;
        }
        @media (max-width: 768px) {
            .card-img-top {
                height: 150px;
            }
            .card-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <a href="../index.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Lista de Produtos</h1>
            <a href="../view/cadastro_produto.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Novo Produto
            </a>
        </div>

        <!-- Mensagens -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Listagem -->
        <?php if (empty($produtos)): ?>
            <div class="empty-state">
                <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                <h3 class="mt-3">Nenhum produto cadastrado</h3>
                <p class="text-muted">Comece cadastrando seu primeiro produto</p>
                <a href="../view/cadastro_produto.php" class="btn btn-primary mt-2">
                    <i class="bi bi-plus-lg"></i> Cadastrar Produto
                </a>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($produtos as $produto): ?>
                    <div class="col">
                        <div class="card h-100" style="cursor: pointer;" 
                             onclick="mostrarDetalhes(<?= $produto->getId() ?>, '<?= htmlspecialchars($produto->getNome(), ENT_QUOTES, 'UTF-8') ?>')">
                            <?php if ($produto->getFoto()): ?>
                                <img src="<?= htmlspecialchars($produto->getFoto(), ENT_QUOTES, 'UTF-8') ?>" 
                                     class="card-img-top" alt="Foto do produto">
                            <?php else: ?>
                                <div class="card-img-top d-flex align-items-center justify-content-center text-muted">
                                    Sem imagem
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($produto->getNome(), ENT_QUOTES, 'UTF-8') ?></h5>
                                <p class="card-text text-muted">
                                    Estoque: <?= $produto->getEstoque() ?><br>
                                    Fornecedor: <?= htmlspecialchars($produto->getFornecedor(), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?php if ($totalProdutos > $itensPorPagina): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination">
                        <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>">Anterior</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= ceil($totalProdutos / $itensPorPagina); $i++): ?>
                            <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagina < ceil($totalProdutos / $itensPorPagina)): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>">Próxima</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal fade" id="detalhesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="produtoNome"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="mensagemErro" class="alert alert-danger alert-dismissible fade show" role="alert">
                        <span id="mensagemErroTexto"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div id="mensagemSucesso" class="alert alert-success alert-dismissible fade show" role="alert">
                        Produto atualizado com sucesso!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <img id="produtoFoto" src="" class="img-fluid mb-3" alt="Foto do produto" style="max-height: 200px; object-fit: contain;">
                            <input type="file" id="produtoFotoInput" name="foto" class="form-control d-none" accept="image/jpeg,image/png,image/gif">
                        </div>
                        <div class="col-md-8">
                            <div id="visualizacao">
                                <p><strong>Descrição:</strong> <span id="produtoDescricao"></span></p>
                                <p><strong>Fornecedor:</strong> <span id="produtoFornecedor"></span></p>
                                <p><strong>Estoque:</strong> <span id="produtoEstoque"></span></p>
                            </div>
                            <form id="editarForm" class="d-none">
                                <input type="hidden" id="produtoId" name="id">
                                <div class="mb-3">
                                    <label for="produtoNomeInput" class="form-label">Nome *</label>
                                    <input type="text" class="form-control" id="produtoNomeInput" name="nome" required>
                                </div>
                                <div class="mb-3">
                                    <label for="produtoDescricaoInput" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="produtoDescricaoInput" name="descricao" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="produtoFornecedorInput" class="form-label">Fornecedor *</label>
                                    <input type="text" class="form-control" id="produtoFornecedorInput" name="fornecedor" required>
                                </div>
                                <div class="mb-3">
                                    <label for="produtoEstoqueInput" class="form-label">Estoque *</label>
                                    <input type="number" class="form-control" id="produtoEstoqueInput" name="estoque" min="0" required>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button id="btnEditar" class="btn btn-primary" onclick="alternarEdicao()">
                        <i class="bi bi-pencil"></i> Editar
                    </button>
                    <button id="btnSalvar" class="btn btn-primary d-none" onclick="salvarProduto()">
                        <i class="bi bi-save"></i> Salvar
                    </button>
                    <button id="btnExcluir" class="btn btn-danger" onclick="confirmarExclusao()">
                        <i class="bi bi-trash"></i> Excluir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o produto "<span id="confirmProdutoNome"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a id="btnConfirmarExclusao" href="#" class="btn btn-danger">Excluir</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentProdutoId = null;
        let isEditando = false;

        function mostrarDetalhes(id, nome) {
            currentProdutoId = id;
            fetch(`../controllers/get_produto.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    document.getElementById('produtoNome').textContent = data.nome;
                    document.getElementById('produtoDescricao').textContent = data.descricao || 'Nenhuma';
                    document.getElementById('produtoFornecedor').textContent = data.fornecedor;
                    document.getElementById('produtoEstoque').textContent = data.estoque;
                    document.getElementById('produtoFoto').src = data.foto || 'https://via.placeholder.com/200';
                    document.getElementById('btnConfirmarExclusao').href = `../view/excluir_produto.php?id=${id}`;

                    // Preencher campos do formulário
                    document.getElementById('produtoId').value = id;
                    document.getElementById('produtoNomeInput').value = data.nome;
                    document.getElementById('produtoDescricaoInput').value = data.descricao || '';
                    document.getElementById('produtoFornecedorInput').value = data.fornecedor;
                    document.getElementById('produtoEstoqueInput').value = data.estoque;

                    // Resetar estado
                    isEditando = false;
                    document.getElementById('visualizacao').classList.remove('d-none');
                    document.getElementById('editarForm').classList.add('d-none');
                    document.getElementById('btnEditar').classList.remove('d-none');
                    document.getElementById('btnSalvar').classList.add('d-none');
                    document.getElementById('produtoFotoInput').classList.add('d-none');
                    document.getElementById('mensagemErro').style.display = 'none';
                    document.getElementById('mensagemSucesso').style.display = 'none';

                    const modal = new bootstrap.Modal(document.getElementById('detalhesModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Erro ao buscar detalhes:', error);
                    alert('Erro ao carregar detalhes do produto');
                });
        }

        function alternarEdicao() {
            isEditando = !isEditando;
            if (isEditando) {
                document.getElementById('visualizacao').classList.add('d-none');
                document.getElementById('editarForm').classList.remove('d-none');
                document.getElementById('btnEditar').classList.add('d-none');
                document.getElementById('btnSalvar').classList.remove('d-none');
                document.getElementById('produtoFotoInput').classList.remove('d-none');
            } else {
                document.getElementById('visualizacao').classList.remove('d-none');
                document.getElementById('editarForm').classList.add('d-none');
                document.getElementById('btnEditar').classList.remove('d-none');
                document.getElementById('btnSalvar').classList.add('d-none');
                document.getElementById('produtoFotoInput').classList.add('d-none');
            }
        }

        function salvarProduto() {
            const form = document.getElementById('editarForm');
            const formData = new FormData(form);
            if (document.getElementById('produtoFotoInput').files[0]) {
                formData.append('foto', document.getElementById('produtoFotoInput').files[0]);
            }

            fetch('../controllers/atualizar_produto.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('mensagemSucesso').style.display = 'block';
                        document.getElementById('mensagemErro').style.display = 'none';
                        // Atualizar visualização
                        document.getElementById('produtoNome').textContent = formData.get('nome');
                        document.getElementById('produtoDescricao').textContent = formData.get('descricao') || 'Nenhuma';
                        document.getElementById('produtoFornecedor').textContent = formData.get('fornecedor');
                        document.getElementById('produtoEstoque').textContent = formData.get('estoque');
                        if (data.foto) {
                            document.getElementById('produtoFoto').src = data.foto;
                        }
                        alternarEdicao();
                    } else {
                        document.getElementById('mensagemErroTexto').textContent = data.error;
                        document.getElementById('mensagemErro').style.display = 'block';
                        document.getElementById('mensagemSucesso').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Erro ao salvar:', error);
                    document.getElementById('mensagemErroTexto').textContent = 'Erro ao salvar o produto';
                    document.getElementById('mensagemErro').style.display = 'block';
                    document.getElementById('mensagemSucesso').style.display = 'none';
                });
        }

        function confirmarExclusao() {
            document.getElementById('confirmProdutoNome').textContent = document.getElementById('produtoNome').textContent;
            const detalhesModal = bootstrap.Modal.getInstance(document.getElementById('detalhesModal'));
            detalhesModal.hide();
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            confirmModal.show();
        }
    </script>
</body>
</html>