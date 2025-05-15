// dashboard.js
let currentProdutoId = null;
let isEditando = false;
let fornecedores = []; // Will be set by dashboard.php
const itensPorPagina = 12;
let paginaAtual = 1;
let termoBusca = '';
let isCarregando = false;
let todosCarregados = false;
let debounceTimer;

// Função para carregar produtos
function carregarProdutos(termo = '', pagina = 1, append = false) {
    if (isCarregando || todosCarregados) return;
    
    isCarregando = true;
    const produtosContainer = document.getElementById('produtosContainer');
    const loading = document.getElementById('loading');
    
    loading.classList.remove('d-none');

    fetch(`../controllers/buscar_produtos.php?termo=${encodeURIComponent(termo)}&pagina=${pagina}`)
        .then(response => response.json())
        .then(data => {
            isCarregando = false;
            loading.classList.add('d-none');

            if (!data.success) {
                mostrarMensagemErro(data.error || 'Erro ao carregar produtos');
                return;
            }

            if (data.total === 0) {
                mostrarMensagemVazia(termo);
                return;
            }

            const produtosHtml = data.produtos.map(produto => criarCardProduto(produto)).join('');
            
            if (!append) {
                produtosContainer.innerHTML = `
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
                        ${produtosHtml}
                    </div>
                `;
            } else {
                const row = produtosContainer.querySelector('.row');
                row.insertAdjacentHTML('beforeend', produtosHtml);
            }

            atualizarEstadoPaginacao(data.total, pagina);
        })
        .catch(error => {
            isCarregando = false;
            loading.classList.add('d-none');
            mostrarMensagemErro('Erro ao carregar produtos: ' + error.message);
        });
}

// Função para criar o card do produto
function criarCardProduto(produto) {
    const fotoUrl = produto.foto ? `data:image/jpeg;base64,${produto.foto}` : 'https://via.placeholder.com/200?text=Sem+Imagem';
    const precoFormatado = (produto.preco ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    return `
        <div class="col">
            <div class="card h-100 produto-card" onclick="mostrarDetalhes(${produto.id})">
                <div class="card-img-container">
                    <img src="${fotoUrl}" class="card-img-top" alt="Foto do produto" loading="lazy">
                </div>
                <div class="card-body">
                    <h5 class="card-title text-truncate" title="${produto.nome}">${produto.nome}</h5>
                    <p class="card-text">
                        <span class="preco">R$ ${precoFormatado}</span>
                        <span class="estoque ${produto.quantidade <= 5 ? 'estoque-baixo' : ''}">
                            Estoque: ${produto.quantidade ?? 0}
                        </span>
                    </p>
                    <p class="card-text fornecedor text-truncate" title="${produto.fornecedor_nome || 'Sem fornecedor'}">
                        ${produto.fornecedor_nome || 'Sem fornecedor'}
                    </p>
                    <div class="input-group mb-2">
                        <input type="number" id="quantidade-${produto.id}" value="1" min="1" max="${produto.quantidade}" class="form-control form-control-sm" style="width: 70px;">
                        <button type="button" class="btn btn-primary btn-sm" onclick="event.stopPropagation(); carrinho.adicionarItem(${produto.id}, document.getElementById('quantidade-${produto.id}').value)">
                            <i class="bi bi-cart-plus"></i> Adicionar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Função para mostrar mensagem de erro
function mostrarMensagemErro(mensagem) {
    document.getElementById('produtosContainer').innerHTML = `
        <div class="empty-state">
            <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
            <h3 class="mt-3">${mensagem}</h3>
        </div>
    `;
    todosCarregados = true;
    document.getElementById('sentinela').style.display = 'none';
}

// Função para mostrar mensagem quando não há produtos
function mostrarMensagemVazia(termo) {
    const mensagem = termo 
        ? `Nenhum produto encontrado para "${termo}"`
        : 'Nenhum produto cadastrado';
    
    document.getElementById('produtosContainer').innerHTML = `
        <div class="empty-state">
            <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
            <h3 class="mt-3">${mensagem}</h3>
        </div>
    `;
    todosCarregados = true;
    document.getElementById('sentinela').style.display = 'none';
}

// Função para atualizar estado da paginação
function atualizarEstadoPaginacao(total, pagina) {
    todosCarregados = total <= pagina * itensPorPagina;
    document.getElementById('sentinela').style.display = todosCarregados ? 'none' : 'block';
    paginaAtual = pagina;
}

// Função para mostrar detalhes do produto
function mostrarDetalhes(id) {
    currentProdutoId = id;
    fetch(`../controllers/get_produto.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            // Elementos de visualização (sempre preenchidos)
            document.getElementById('produtoNome').textContent = data.produto.nome;
            document.getElementById('produtoDescricao').textContent = data.produto.descricao || 'Nenhuma';
            document.getElementById('produtoFornecedor').textContent = data.produto.fornecedor_nome || 'Sem fornecedor';
            document.getElementById('produtoEstoque').textContent = data.produto.quantidade;
            document.getElementById('produtoPreco').textContent = `R$ ${(data.produto.preco ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            document.getElementById('produtoFoto').src = data.produto.foto ? `data:image/jpeg;base64,${data.produto.foto}` : 'https://via.placeholder.com/200';

            // Elementos do formulário de edição (sempre existem no DOM, mesmo que d-none)
            document.getElementById('produtoId').value = id;
            document.getElementById('produtoNomeInput').value = data.produto.nome;
            document.getElementById('produtoDescricaoInput').value = data.produto.descricao || '';
            document.getElementById('produtoEstoqueInput').value = data.produto.quantidade;
            document.getElementById('produtoPrecoInput').value = data.produto.preco ?? 0;

            const fornecedorSelect = document.getElementById('produtoFornecedorInput');
            fornecedorSelect.innerHTML = '<option value="">Selecione um fornecedor</option>';
            if (window.fornecedores && window.fornecedores.length > 0) {
                window.fornecedores.forEach(fornecedor => {
                    const option = document.createElement('option');
                    option.value = fornecedor.id;
                    option.text = fornecedor.nome;
                    if (fornecedor.id == data.produto.fornecedor_id) {
                        option.selected = true;
                    }
                    fornecedorSelect.appendChild(option);
                });
            }

            // Link do botão de exclusão no modal de confirmação (sempre existe no DOM)
            const btnConfirmarExclusao = document.getElementById('btnConfirmarExclusao');
            if (btnConfirmarExclusao) {
                btnConfirmarExclusao.href = `../controllers/excluir_produto.php?id=${id}`;
            }

            // Botões de admin (condicionalmente renderizados no PHP)
            const btnEditar = document.getElementById('btnEditar');
            const btnExcluir = document.getElementById('btnExcluir');
            const btnSalvar = document.getElementById('btnSalvar');

            if (window.isAdmin) {
                if (btnEditar) btnEditar.classList.remove('d-none');
                if (btnExcluir) btnExcluir.classList.remove('d-none');
                // btnSalvar começa escondido, é mostrado por alternarEdicao
                if (btnSalvar) btnSalvar.classList.add('d-none'); 
            } else {
                // Para não-admins, esses botões não estão no DOM ou devem permanecer escondidos
                // Nenhuma ação necessária aqui se o PHP já os removeu do DOM.
                // Se estivessem no DOM e precisassem ser escondidos, faríamos:
                // if (btnEditar) btnEditar.classList.add('d-none');
                // if (btnExcluir) btnExcluir.classList.add('d-none');
                // if (btnSalvar) btnSalvar.classList.add('d-none');
            }

            // Resetar estado do modal
            isEditando = false;
            document.getElementById('visualizacao').classList.remove('d-none');
            document.getElementById('editarForm').classList.add('d-none');
            document.getElementById('produtoFotoInput').classList.add('d-none');
            document.getElementById('mensagemErro').style.display = 'none';
            document.getElementById('mensagemSucesso').style.display = 'none';

            const modal = new bootstrap.Modal(document.getElementById('produtoModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Erro ao carregar detalhes do produto:', error);
            alert('Erro ao carregar detalhes do produto: ' + error.message);
        });
}

// Função para alternar modo de edição
function alternarEdicao() {
    isEditando = !isEditando;
    document.getElementById('visualizacao').classList.toggle('d-none');
    document.getElementById('editarForm').classList.toggle('d-none');
    document.getElementById('btnEditar').classList.toggle('d-none');
    document.getElementById('btnSalvar').classList.toggle('d-none');
    document.getElementById('produtoFotoInput').classList.toggle('d-none');
}

// Função para salvar produto
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
                // Fechar o modal e remover o backdrop
                const modal = bootstrap.Modal.getInstance(document.getElementById('produtoModal'));
                modal.hide();
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                
                // Recarregar a página
                window.location.reload();
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

// Função para confirmar exclusão
function confirmarExclusao() {
    document.getElementById('confirmProdutoNome').textContent = document.getElementById('produtoNome').textContent;
    const detalhesModal = bootstrap.Modal.getInstance(document.getElementById('produtoModal'));
    detalhesModal.hide();
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    confirmModal.show();
}

// Função para adicionar produto do modal ao carrinho
function adicionarProdutoDoModalAoCarrinho() {
    const quantidadeInput = document.getElementById('quantidadeModalProduto');
    const quantidade = parseInt(quantidadeInput.value);

    if (currentProdutoId && quantidade > 0) {
        carrinho.adicionarItem(currentProdutoId, quantidade);
        // Opcional: fechar o modal após adicionar
        // const modal = bootstrap.Modal.getInstance(document.getElementById('produtoModal'));
        // modal.hide();
    } else {
        alert('Por favor, insira uma quantidade válida.');
    }
}

// Configurar eventos
document.addEventListener('DOMContentLoaded', () => {
    // Configurar formulário de cadastro
    const formCadastro = document.getElementById('formCadastroProduto');
    formCadastro.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('../controllers/cadastrar_produto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fechar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('cadastroProdutoModal'));
                modal.hide();
                
                // Limpar formulário
                formCadastro.reset();
                
                // Recarregar a página
                window.location.reload();
                
                // Mostrar mensagem de sucesso
                alert('Produto cadastrado com sucesso!');
            } else {
                alert(data.error || 'Erro ao cadastrar produto');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao cadastrar produto');
        });
    });
});

function exibirProduto(produto) {
    if (produto.foto) {
        const fotoBase64 = btoa(String.fromCharCode.apply(null, new Uint8Array(produto.foto)));
        document.getElementById('produtoFoto').src = `data:image/jpeg;base64,${fotoBase64}`;
    } else {
        document.getElementById('produtoFoto').src = 'https://via.placeholder.com/200';
    }
}
