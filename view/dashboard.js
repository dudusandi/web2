// dashboard.js
let currentProdutoId = null;
let isEditando = false;
let fornecedores = []; // Will be set by dashboard.php

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
                const modal = bootstrap.Modal.getInstance(document.getElementById('produtoModal'));
                modal.hide();
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
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
    } else {
        alert('Por favor, insira uma quantidade válida.');
    }
}

// Configurar eventos
document.addEventListener('DOMContentLoaded', () => {
    const formCadastro = document.getElementById('formCadastroProduto');
    // Verificar se o formulário de cadastro existe antes de adicionar o listener
    if (formCadastro) {
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('cadastroProdutoModal'));
                    modal.hide();
                    formCadastro.reset();
                    window.location.reload();
                } else {
                    alert(data.error || 'Erro ao cadastrar produto');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao cadastrar produto');
            });
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
