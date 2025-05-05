<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../model/produto.php';
require_once __DIR__ . '/../dao/fornecedor_dao.php'; 

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    $fornecedorDao = new FornecedorDAO($pdo); 

    // Busca lista de fornecedores
    $fornecedores = [];
    try {
        $fornecedores = $fornecedorDao->listarFornecedores();
    } catch (Exception $e) {
        error_log("Erro ao listar fornecedores: " . $e->getMessage());
        $mensagem = "Erro ao carregar fornecedores: " . $e->getMessage();
        $tipoMensagem = 'erro';
    }

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
    <title>UcsExpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="header">
        <div class="logo">UCS<span>express</span></div>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Pesquisar produtos..." autocomplete="off">
        </div>
        <div class="user-options">
            <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
            <a href="../controllers/logout_controller.php">Sair</a>
            <div class="cart">
                <span>0</span>
                🛒
            </div>
        </div>
    </div>
    <!-- Menu com visualização apenas para o admin -->
    <div class="nav-bar">
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <a href="../view/cadastro_produto.php">Cadastrar Produto</a> 
            <a href="../view/listar_fornecedor.php">Editar Fornecedores</a> 
            <a href="../view/listar_clientes.php">Editar Clientes</a> 
        <?php endif; ?>
    </div>
    <div class="welcome">
    </div>

    <!-- Seção de Produtos -->
    <div class="products-section container">
        <!-- Mensagens -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Listagem -->
        <div id="produtosContainer">
            <?php
            $itensPorPagina = 6;
            $produtos = $produtoDao->listarTodosProdutos($itensPorPagina, 0);
            if (empty($produtos)): ?>
                <div class="empty-state">
                    <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                    <h3 class="mt-3">Nenhum produto cadastrado</h3>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($produtos as $produto): ?>
                        <div class="col">
                            <div class="card h-100" style="cursor: pointer;" 
                                onclick="mostrarDetalhes(<?= $produto->getId() ?>, '<?= htmlspecialchars($produto->getNome(), ENT_QUOTES, 'UTF-8') ?>', <?= $produto->getUsuarioId() ?>)">
                                <?php if ($produto->getFoto()): ?>
                                    <img src="<?= htmlspecialchars('../public/uploads/imagens/' . $produto->getFoto(), ENT_QUOTES, 'UTF-8') ?>" 
                                         class="card-img-top" alt="Foto do produto">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center text-muted">
                                        Sem imagem
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($produto->getNome(), ENT_QUOTES, 'UTF-8') ?></h5>
                                    <p class="card-text text-muted">
                                        Estoque: <?= $produto->getQuantidade() ?? 0 ?><br>
                                        Preço: R$ <?= number_format($produto->getPreco() ?? 0, 2, ',', '.') ?><br>
                                        Fornecedor: <?= htmlspecialchars($produto->fornecedor_nome ?? 'Sem fornecedor', ENT_QUOTES, 'UTF-8') ?>
                                    </p>
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
                                <p><strong>Preço:</strong> <span id="produtoPreco"></span></p>
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
                                    <select class="form-control" id="produtoFornecedorInput" name="fornecedor" required></select>
                                </div>
                                <div class="mb-3">
                                    <label for="produtoEstoqueInput" class="form-label">Estoque *</label>
                                    <input type="number" class="form-control" id="produtoEstoqueInput" name="estoque" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="produtoPrecoInput" class="form-label">Preço *</label>
                                    <input type="number" step="0.01" class="form-control" id="produtoPrecoInput" name="preco" required>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button id="btnEditar" class="btn btn-primary d-none" onclick="alternarEdicao()">
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
        let fornecedores = <?php echo json_encode($fornecedores); ?>;
        const itensPorPagina = 6;

        // Função para carregar produtos
        function carregarProdutos(termo = '', pagina = 1) {
            fetch(`../controllers/buscar_produtos.php?termo=${encodeURIComponent(termo)}&pagina=${pagina}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('produtosContainer').innerHTML = `
                            <div class="empty-state">
                                <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                                <h3 class="mt-3">Erro: ${data.error}</h3>
                            </div>
                        `;
                        return;
                    }

                    const produtosContainer = document.getElementById('produtosContainer');
                    const produtosHtml = data.produtos.map(produto => `
                        <div class="col">
                            <div class="card h-100" style="cursor: pointer;" 
                                onclick="mostrarDetalhes(${produto.id}, '${produto.nome}', ${produto.usuario_id})">
                                ${produto.foto ? `<img src="../public/uploads/imagens/${produto.foto}" class="card-img-top" alt="Foto do produto">` : `
                                    <div class="card-img-top d-flex align-items-center justify-content-center text-muted">
                                        Sem imagem
                                    </div>
                                `}
                                <div class="card-body">
                                    <h5 class="card-title">${produto.nome}</h5>
                                    <p class="card-text text-muted">
                                        Estoque: ${produto.quantidade ?? 0}<br>
                                        Preço: R$ ${number_format(produto.preco ?? 0, 2, ',', '.')}<br>
                                        Fornecedor: ${produto.fornecedor_nome}
                                    </p>
                                </div>
                            </div>
                        </div>
                    `).join('');

                    produtosContainer.innerHTML = `
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            ${produtosHtml}
                        </div>
                        ${data.total > itensPorPagina ? `
                            <nav id="paginacao" aria-label="Page navigation" class="mt-4">
                                <ul class="pagination">
                                    ${pagina > 1 ? `<li class="page-item"><a class="page-link" href="#" onclick="carregarProdutos('${termo}', ${pagina - 1})">Anterior</a></li>` : ''}
                                    ${Array.from({ length: Math.ceil(data.total / itensPorPagina) }, (_, i) => `
                                        <li class="page-item ${i + 1 === pagina ? 'active' : ''}">
                                            <a class="page-link" href="#" onclick="carregarProdutos('${termo}', ${i + 1})">${i + 1}</a>
                                        </li>
                                    `).join('')}
                                    ${pagina < Math.ceil(data.total / itensPorPagina) ? `<li class="page-item"><a class="page-link" href="#" onclick="carregarProdutos('${termo}', ${pagina + 1})">Próxima</a></li>` : ''}
                                </ul>
                            </nav>
                        ` : ''}
                    `;

                    if (data.total === 0 && termo) {
                        produtosContainer.innerHTML = `
                            <div class="empty-state">
                                <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                                <h3 class="mt-3">Nenhum produto encontrado para "${termo}"</h3>
                            </div>
                        `;
                    } else if (data.total === 0) {
                        produtosContainer.innerHTML = `
                            <div class="empty-state">
                                <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                                <h3 class="mt-3">Nenhum produto cadastrado</h3>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar produtos:', error);
                    document.getElementById('produtosContainer').innerHTML = `
                        <div class="empty-state">
                            <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                            <h3 class="mt-3">Erro ao carregar produtos</h3>
                        </div>
                    `;
                });
        }

        // Evento de busca
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter' || e.target.value.length >= 3) {
                carregarProdutos(e.target.value, 1);
            } else if (e.target.value === '') {
                carregarProdutos('', 1);
            }
        });

        document.getElementById('searchButton').addEventListener('click', function() {
            const termo = document.getElementById('searchInput').value;
            carregarProdutos(termo, 1);
        });

        // Carregar produtos iniciais
        carregarProdutos('', 1);

        function mostrarDetalhes(id, nome, usuarioId) {
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
                    document.getElementById('produtoFornecedor').textContent = data.fornecedor_nome || 'Sem fornecedor';
                    document.getElementById('produtoEstoque').textContent = data.estoque;
                    document.getElementById('produtoPreco').textContent = `R$ ${number_format(data.preco ?? 0, 2, ',', '.')}`;
                    document.getElementById('produtoFoto').src = data.foto ? `../public/uploads/imagens/${data.foto}` : 'https://via.placeholder.com/200';
                    document.getElementById('btnConfirmarExclusao').href = `../controllers/excluir_produto.php?id=${id}`;

                    document.getElementById('produtoId').value = id;
                    document.getElementById('produtoNomeInput').value = data.nome;
                    document.getElementById('produtoDescricaoInput').value = data.descricao || '';
                    document.getElementById('produtoEstoqueInput').value = data.estoque;
                    document.getElementById('produtoPrecoInput').value = data.preco ?? 0;

                    const fornecedorSelect = document.getElementById('produtoFornecedorInput');
                    fornecedorSelect.innerHTML = '';
                    fornecedores.forEach(fornecedor => {
                        const option = document.createElement('option');
                        option.value = fornecedor.id;
                        option.text = fornecedor.nome;
                        if (fornecedor.id == data.fornecedor) {
                            option.selected = true;
                        }
                        fornecedorSelect.appendChild(option);
                    });

                    const usuarioLogadoId = <?php echo json_encode($_SESSION['usuario_id']); ?>;
                    const isAdmin = <?php echo json_encode(isset($_SESSION['is_admin']) && $_SESSION['is_admin']); ?>;
                    const btnEditar = document.getElementById('btnEditar');
                    const btnExcluir = document.getElementById('btnExcluir');
                    
                    if (isAdmin || usuarioId == usuarioLogadoId) {
                        btnEditar.classList.remove('d-none');
                        btnExcluir.classList.remove('d-none');
                    } else {
                        btnEditar.classList.add('d-none');
                        btnExcluir.classList.add('d-none');
                    }

                    isEditando = false;
                    document.getElementById('visualizacao').classList.remove('d-none');
                    document.getElementById('editarForm').classList.add('d-none');
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
                        document.getElementById('produtoNome').textContent = formData.get('nome');
                        document.getElementById('produtoDescricao').textContent = formData.get('descricao') || 'Nenhuma';
                        const fornecedorId = formData.get('fornecedor');
                        const fornecedorNome = fornecedores.find(f => f.id == fornecedorId)?.nome || 'Sem fornecedor';
                        document.getElementById('produtoFornecedor').textContent = fornecedorNome;
                        document.getElementById('produtoEstoque').textContent = formData.get('estoque');
                        document.getElementById('produtoPreco').textContent = `R$ ${number_format(formData.get('preco'), 2, ',', '.')}`;
                        if (data.foto) {
                            document.getElementById('produtoFoto').src = `../public/uploads/imagens/${data.foto}`;
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

        function number_format(number, decimals, dec_point, thousands_sep) {
            number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
            var n = !isFinite(+number) ? 0 : +number,
                prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                s = '',
                toFixedFix = function (n, prec) {
                    var k = Math.pow(10, prec);
                    return '' + Math.round(n * k) / k;
                };
            s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
            if (s[0].length > 3) {
                s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
            }
            if ((s[1] || '').length < prec) {
                s[1] = s[1] || '';
                s[1] += new Array(prec - s[1].length + 1).join('0');
            }
            return s.join(dec);
        }
    </script>
</body>
</html>