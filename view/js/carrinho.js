class Carrinho {
    constructor() {
        try {
            const carrinhoSalvo = localStorage.getItem('carrinho');
            this.itens = carrinhoSalvo ? JSON.parse(carrinhoSalvo) : {};
            if (!this.itens || typeof this.itens !== 'object') {
                this.itens = {};
            }
        } catch (error) {
            console.error('Erro ao carregar carrinho:', error);
            this.itens = {};
        }
        this.onChange = null;
        this.atualizarContador();
    }

    adicionarItem(produtoId, quantidade = 1) {
        try {
            produtoId = parseInt(produtoId);
            quantidade = parseInt(quantidade);

            if (isNaN(produtoId) || isNaN(quantidade) || quantidade <= 0) {
                throw new Error('Parâmetros inválidos');
            }

            if (this.itens[produtoId]) {
                this.itens[produtoId] += quantidade;
            } else {
                this.itens[produtoId] = quantidade;
            }
            this.salvar();
            this.atualizarContador();
            this.mostrarNotificacao('Produto adicionado ao carrinho!');
            this.notificarMudanca();
        } catch (error) {
            console.error('Erro ao adicionar item:', error);
            this.mostrarNotificacao('Erro ao adicionar produto ao carrinho!', true);
        }
    }

    removerItem(produtoId) {
        try {
            produtoId = parseInt(produtoId);
            if (isNaN(produtoId)) {
                throw new Error('ID do produto inválido');
            }

            delete this.itens[produtoId];
            this.salvar();
            this.atualizarContador();
            this.mostrarNotificacao('Produto removido do carrinho!');
            this.notificarMudanca();
        } catch (error) {
            console.error('Erro ao remover item:', error);
            this.mostrarNotificacao('Erro ao remover produto do carrinho!', true);
        }
    }

    atualizarQuantidade(produtoId, quantidade) {
        try {
            produtoId = parseInt(produtoId);
            quantidade = parseInt(quantidade);

            if (isNaN(produtoId) || isNaN(quantidade)) {
                throw new Error('Parâmetros inválidos');
            }

            if (quantidade > 0) {
                this.itens[produtoId] = quantidade;
            } else {
                this.removerItem(produtoId);
            }
            this.salvar();
            this.atualizarContador();
            this.notificarMudanca();
        } catch (error) {
            console.error('Erro ao atualizar quantidade:', error);
            this.mostrarNotificacao('Erro ao atualizar quantidade!', true);
        }
    }

    limpar() {
        try {
            this.itens = {};
            this.salvar();
            this.atualizarContador();
            this.mostrarNotificacao('Carrinho limpo!');
            this.notificarMudanca();
        } catch (error) {
            console.error('Erro ao limpar carrinho:', error);
            this.mostrarNotificacao('Erro ao limpar carrinho!', true);
        }
    }

    salvar() {
        try {
            localStorage.setItem('carrinho', JSON.stringify(this.itens));
        } catch (error) {
            console.error('Erro ao salvar carrinho:', error);
            this.mostrarNotificacao('Erro ao salvar carrinho!', true);
        }
    }

    obterItens() {
        return this.itens;
    }

    obterQuantidadeTotal() {
        return Object.values(this.itens).reduce((total, qtd) => total + parseInt(qtd), 0);
    }

    atualizarContador() {
        try {
            const contador = document.getElementById('contador-carrinho');
            if (contador) {
                const total = this.obterQuantidadeTotal();
                contador.textContent = total;
                contador.style.display = total > 0 ? 'inline' : 'none';
            }
        } catch (error) {
            console.error('Erro ao atualizar contador:', error);
        }
    }

    mostrarNotificacao(mensagem, isError = false) {
        try {
            const notificacao = document.createElement('div');
            notificacao.className = `notificacao-carrinho ${isError ? 'erro' : ''}`;
            notificacao.textContent = mensagem;
            document.body.appendChild(notificacao);

            setTimeout(() => {
                notificacao.remove();
            }, 3000);
        } catch (error) {
            console.error('Erro ao mostrar notificação:', error);
        }
    }

    notificarMudanca() {
        if (typeof this.onChange === 'function') {
            try {
                this.onChange();
            } catch (error) {
                console.error('Erro ao notificar mudança:', error);
            }
        }
    }
}

// Inicializar o carrinho
const carrinho = new Carrinho();

// Adicionar estilos para a notificação
const style = document.createElement('style');
style.textContent = `
    .notificacao-carrinho {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #4CAF50;
        color: white;
        padding: 15px 25px;
        border-radius: 4px;
        z-index: 1000;
        animation: slideIn 0.5s ease-out;
    }

    .notificacao-carrinho.erro {
        background-color: #f44336;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    #contador-carrinho {
        background-color: #ff4444;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        margin-left: 5px;
    }
`;
document.head.appendChild(style); 