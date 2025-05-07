// dashboard.js
let currentProdutoId = null;
let isEditando = false;
let fornecedores = []; // Will be set by dashboard.php
const itensPorPagina = 6;
let paginaAtual = 1;
let termoBusca = '';
let isCarregando = false;
let todosCarregados = false;

// Função para carregar produtos
function carregarProdutos(termo = '', pagina = 1, append = false) {
    if (isCarregando || todosCarregados) {
        console.log(`Carregamento bloqueado: isCarregando=${isCarregando}, todosCarregados=${todosCarregados}`);
        return;
    }
    isCarregando = true;
    document.getElementById('produtosContainer').setAttribute('aria-busy', 'true');
    document.getElementById('loading').classList.remove('d-none');
    console.log(`Carregando produtos: termo="${termo}", pagina=${pagina}, append=${append}`);

    fetch(`../controllers/buscar_produtos.php?termo=${encodeURIComponent(termo)}&pagina=${pagina}`)
        .then(response => {
            console.log(`Resposta do servidor: status=${response.status}`);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            isCarregando = false;
            document.getElementById('produtosContainer').setAttribute('aria-busy', 'false');
            document.getElementById('loading').classList.add('d-none');

            if (data.error) {
                console.error('Erro retornado pelo servidor:', data.error);
                document.getElementById('produtosContainer').innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                        <h3 class="mt-3">Erro: ${data.error}</h3>
                    </div>
                `;
                todosCarregados = true;
                document.getElementById('sentinela').style.display = 'none';
                return;
            }

            const produtosContainer = document.getElementById('produtosContainer');
            const produtosHtml = data.produtos.map(produto => `
                <div class="col">
                    <div class="card h-100" style="cursor: pointer;" 
                        onclick="mostrarDetalhes(${produto.id}, '${produto.nome}', ${produto.usuario_id})">
                        ${produto.foto ? `<img src="../public/uploads/imagens/${produto.foto}" class="card-img-top" alt="Foto do produto" loading="lazy">` : `
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

            if (!append) {
                produtosContainer.innerHTML = `
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        ${produtosHtml}
                    </div>
                `;
            } else {
                const row = produtosContainer.querySelector('.row') || document.createElement('div');
                if (!row.classList.contains('row')) {
                    row.className = 'row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4';
                    produtosContainer.appendChild(row);
                }
                row.insertAdjacentHTML('beforeend', produtosHtml);
            }

            // Verifica se todos os produtos foram carregados
            if (data.produtos.length < itensPorPagina || data.total <= pagina * itensPorPagina) {
                todosCarregados = true;
                document.getElementById('sentinela').style.display = 'none';
                console.log('Todos os produtos foram carregados');
            } else {
                todosCarregados = false;
                document.getElementById('sentinela').style.display = 'block';
                paginaAtual = pagina;
                console.log(`Mais produtos disponíveis, página atual: ${paginaAtual}`);
            }

            if (data.total === 0 && termo) {
                produtosContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                        <h3 class="mt-3">Nenhum produto encontrado para "${termo}"</h3>
                    </div>
                `;
                todosCarregados = true;
                document.getElementById('sentinela').style.display = 'none';
            } else if (data.total === 0) {
                produtosContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                        <h3 class="mt-3">Nenhum produto cadastrado</h3>
                    </div>
                `;
                todosCarregados = true;
                document.getElementById('sentinela').style.display = 'none';
            }

            console.log(`Página: ${pagina}, Produtos carregados: ${data.produtos.length}, Total: ${data.total}, Todos carregados: ${todosCarregados}`);
        })
        .catch(error => {
            isCarregando = false;
            document.getElementById('produtosContainer').setAttribute('aria-busy', 'false');
            document.getElementById('loading').classList.add('d-none');
            console.error('Erro ao carregar produtos:', error);
            document.getElementById('produtosContainer').innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                    <h3 class="mt-3">Erro ao carregar produtos</h3>
                </div>
            `;
            todosCarregados = true;
            document.getElementById('sentinela').style.display = 'none';
        });
}

// Configurar Intersection Observer para o carregamento infinito
document.addEventListener('DOMContentLoaded', () => {
    const sentinela = document.getElementById('sentinela');
    if (!sentinela) {
        console.error('Elemento #sentinela não encontrado no DOM');
        return;
    }

    console.log('Configurando Intersection Observer para o sentinela');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            console.log(`Sentinela: isIntersecting=${entry.isIntersecting}, isCarregando=${isCarregando}, todosCarregados=${todosCarregados}, boundingRect=${JSON.stringify(entry.boundingClientRect)}`);
            if (entry.isIntersecting && !isCarregando && !todosCarregados) {
                console.log('Sentinela visível, carregando próxima página...');
                setTimeout(() => {
                    carregarProdutos(termoBusca, paginaAtual + 1, true);
                }, 100); // Debounce para garantir que isCarregando esteja false
            }
        });
    }, {
        root: document.querySelector('.products-section'),
        threshold: 0.1, // Detecta quando 10% do sentinela está visível
        rootMargin: '300px' // Detecta 300px antes do sentinela entrar na viewport
    });
    observer.observe(sentinela);

    // Evento de busca
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const termo = e.target.value;
            console.log(`Evento de busca: termo="${termo}", key="${e.key}"`);
            if (e.key === 'Enter' || termo.length >= 3) {
                termoBusca = termo;
                paginaAtual = 1;
                todosCarregados = false;
                document.getElementById('sentinela').style.display = 'block';
                carregarProdutos(termo, 1, false);
            } else if (termo === '') {
                termoBusca = '';
                paginaAtual = 1;
                todosCarregados = false;
                document.getElementById('sentinela').style.display = 'block';
                carregarProdutos('', 1, false);
            }
        });
    } else {
        console.error('Elemento #searchInput não encontrado no DOM');
    }

    // Carregar produtos iniciais
    console.log('Carregando produtos iniciais');
    carregarProdutos('', 1, false);
});

// Função para mostrar detalhes do produto
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

            const usuarioLogadoId = window.usuarioLogadoId;
            const isAdmin = window.isAdmin;
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

// Função para alternar modo de edição
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
                // Recarregar produtos para refletir alterações
                carregarProdutos(termoBusca, 1, false);
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
    const detalhesModal = bootstrap.Modal.getInstance(document.getElementById('detalhesModal'));
    detalhesModal.hide();
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    confirmModal.show();
}

// Função para formatar números
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