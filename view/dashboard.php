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
require_once __DIR__ . '/../controllers/carrinho.php';

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
            <form id="searchForm" class="d-flex" method="GET">
                <input type="text" id="searchInput" name="termo" placeholder="Pesquisar produtos..." value="<?= htmlspecialchars($_GET['termo'] ?? '') ?>">
                <button type="submit" class="btn-search-custom">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
        <div class="user-options">
            <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
            <a href="../controllers/logout_controller.php">Sair</a>
            <a href="carrinho.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-cart"></i> Carrinho <span id="contador-carrinho" style="display: none;">0</span>
            </a>
        </div>
    </div>
    <!-- Menu com visualização apenas para o admin -->
    <div class="nav-bar">
        <a href="meus-pedidos.php" class="btn btn-outline-info">
            <i class="bi bi-receipt"></i> Meus Pedidos
        </a>
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <a href="admin_listar_pedidos.php" class="btn btn-outline-warning">
                <i class="bi bi-list-check"></i> Todos os Pedidos
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cadastroProdutoModal">
                <i class="bi bi-plus-circle"></i> Cadastrar Produto
            </button>
            <a href="../view/listar_fornecedor.php" class="btn btn-outline-primary">
                <i class="bi bi-building"></i> Editar Fornecedores
            </a> 
            <a href="../view/listar_clientes.php" class="btn btn-outline-primary">
                <i class="bi bi-people"></i> Editar Clientes
            </a> 
        <?php endif; ?>
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
            try {
                $termo = $_GET['termo'] ?? '';
                $produtos = $produtoDao->buscarProdutos($termo);
                if (empty($produtos)) {
                    echo '<div class="empty-state">
                            <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                            <h3 class="mt-3">' . ($termo ? "Nenhum produto encontrado para \"$termo\"" : "Nenhum produto cadastrado") . '</h3>
                          </div>';
                } else {
                    echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">';
                    foreach ($produtos as $produto) {
                        $fotoUrl = $produto['foto'] ? 'data:image/jpeg;base64,' . base64_encode($produto['foto']) : 'https://via.placeholder.com/200?text=Sem+Imagem';
                        $precoFormatado = number_format($produto['preco'], 2, ',', '.');
                        echo '<div class="col">
                                <div class="card h-100 produto-card">
                                    <div class="card-img-container" onclick="mostrarDetalhes(' . $produto['id'] . ')">
                                        <img src="' . $fotoUrl . '" class="card-img-top" alt="Foto do produto">
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-truncate" title="' . htmlspecialchars($produto['nome']) . '" onclick="mostrarDetalhes(' . $produto['id'] . ')">' . htmlspecialchars($produto['nome']) . '</h5>
                                        <p class="card-text">
                                            <span class="preco">R$ ' . $precoFormatado . '</span>
                                            <span class="estoque ' . ($produto['quantidade'] <= 5 ? 'estoque-baixo' : '') . '">
                                                Estoque: ' . $produto['quantidade'] . '
                                            </span>
                                        </p>
                                        <p class="card-text fornecedor text-truncate" title="' . htmlspecialchars($produto['fornecedor_nome']) . '">
                                            ' . htmlspecialchars($produto['fornecedor_nome']) . '
                                        </p>
                                    </div>
                                </div>
                            </div>';
                    }
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Erro ao carregar produtos: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal fade" id="produtoModal" tabindex="-1" aria-hidden="true">
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
                    
                    <!-- Botão Adicionar ao Carrinho (sempre visível) -->
                    <div class="input-group me-auto" style="max-width: 180px;">
                        <input type="number" id="quantidadeModalProduto" value="1" min="1" class="form-control form-control-sm">
                        <button type="button" class="btn btn-success btn-sm" onclick="adicionarProdutoDoModalAoCarrinho()">
                            <i class="bi bi-cart-plus"></i> Adicionar
                        </button>
                    </div>

                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                        <button id="btnEditar" class="btn btn-primary" onclick="alternarEdicao()">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <button id="btnSalvar" class="btn btn-primary d-none" onclick="salvarProduto()">
                            <i class="bi bi-save"></i> Salvar
                        </button>
                        <button id="btnExcluir" class="btn btn-danger" onclick="confirmarExclusao()">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    <?php endif; ?>
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

    <!-- Modal de Cadastro de Produto -->
    <div class="modal fade" id="cadastroProdutoModal" tabindex="-1" aria-labelledby="cadastroProdutoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cadastroProdutoModalLabel">Cadastro de Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <form id="formCadastroProduto" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label">Nome do Produto</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                                <div class="invalid-feedback">
                                    Por favor, informe o nome do produto.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fornecedor" class="form-label">Fornecedor</label>
                                <select class="form-select" id="fornecedor" name="fornecedor_id" required>
                                    <option value="">Selecione um fornecedor</option>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor, selecione um fornecedor.
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantidade" class="form-label">Quantidade</label>
                                <input type="number" class="form-control" id="quantidade" name="quantidade" min="0" required>
                                <div class="invalid-feedback">
                                    Por favor, informe a quantidade.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="preco" class="form-label">Preço</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control" id="preco" name="preco" min="0" step="0.01" required>
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, informe o preço.
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="foto" class="form-label">Foto do Produto</label>
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCadastroProduto" class="btn btn-primary">Cadastrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    window.fornecedores = <?php echo json_encode($fornecedores); ?>;
    window.usuarioLogadoId = <?php echo json_encode($_SESSION['usuario_id']); ?>;
    window.isAdmin = <?php echo json_encode(isset($_SESSION['is_admin']) && $_SESSION['is_admin']); ?>;
    </script>
    <script src="./dashboard.js"></script>
    <script src="js/carrinho.js"></script>
    <script>
        // Preencher select de fornecedores
        document.addEventListener('DOMContentLoaded', function() {
            const fornecedorSelect = document.getElementById('fornecedor');
            if (window.fornecedores && window.fornecedores.length > 0) {
                window.fornecedores.forEach(fornecedor => {
                    const option = document.createElement('option');
                    option.value = fornecedor.id;
                    option.text = fornecedor.nome;
                    fornecedorSelect.appendChild(option);
                });
            }
        });

        function exibirProduto(produto) {
            if (produto.foto) {
                const fotoBase64 = btoa(String.fromCharCode.apply(null, new Uint8Array(produto.foto)));
                document.getElementById('produtoFoto').src = `data:image/jpeg;base64,${fotoBase64}`;
            } else {
                document.getElementById('produtoFoto').src = 'https://via.placeholder.com/200';
            }
        }

        // Debug dos formulários de adicionar ao carrinho
        document.querySelectorAll('form[action="carrinho.php"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                console.log('Formulário enviado:', {
                    acao: this.querySelector('[name="acao"]').value,
                    produto_id: this.querySelector('[name="produto_id"]').value,
                    quantidade: this.querySelector('[name="quantidade"]').value,
                    redirect: this.querySelector('[name="redirect"]').value
                });
            });
        });
    </script>
</body>
</html>