<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
} catch (Exception $e) {
    error_log("Erro ao conectar ao banco: " . $e->getMessage());
    $mensagem = "Erro ao carregar produtos: " . $e->getMessage();
    $tipoMensagem = 'erro';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho - UcsExpress</title>
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
            <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="container mt-4">
        <h2>Seu Carrinho</h2>
        
        <div id="carrinho-vazio" class="text-center py-5" style="display: none;">
            <i class="bi bi-cart-x" style="font-size: 4rem;"></i>
            <h3 class="mt-3">Seu carrinho está vazio</h3>
            <a href="dashboard.php" class="btn btn-primary mt-3">
                <i class="bi bi-arrow-left"></i> Continuar Comprando
            </a>
        </div>

        <div id="carrinho-itens">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Preço</th>
                            <th>Quantidade</th>
                            <th>Subtotal</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="itens-carrinho">
                        <!-- Itens serão inseridos aqui via JavaScript -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td id="total-carrinho" colspan="2"><strong>R$ 0,00</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <button onclick="carrinho.limpar()" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Limpar Carrinho
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Continuar Comprando
                </a>
                <button onclick="finalizarCompra()" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Finalizar Compra
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/carrinho.js"></script>
    <script>
        // Função para atualizar a interface do carrinho
        function atualizarInterfaceCarrinho() {
            const itens = carrinho.obterItens();
            const tbody = document.getElementById('itens-carrinho');
            const carrinhoVazio = document.getElementById('carrinho-vazio');
            const carrinhoItens = document.getElementById('carrinho-itens');
            
            if (Object.keys(itens).length === 0) {
                carrinhoVazio.style.display = 'block';
                carrinhoItens.style.display = 'none';
                return;
            }

            carrinhoVazio.style.display = 'none';
            carrinhoItens.style.display = 'block';
            
            // Buscar detalhes dos produtos
            fetch('../controllers/buscar_produtos.php?ids=' + Object.keys(itens).join(','))
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na resposta do servidor: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.error || 'Erro ao buscar produtos');
                    }

                    const produtos = data.produtos;
                    let html = '';
                    let total = 0;

                    produtos.forEach(produto => {
                        const quantidade = itens[produto.id];
                        const subtotal = produto.preco * quantidade;
                        total += subtotal;

                        html += `
                            <tr>
                                <td>${produto.nome}</td>
                                <td>R$ ${produto.preco.toFixed(2)}</td>
                                <td>
                                    <input type="number" 
                                           value="${quantidade}" 
                                           min="1" 
                                           max="${produto.quantidade}"
                                           onchange="carrinho.atualizarQuantidade(${produto.id}, this.value)"
                                           class="form-control form-control-sm" 
                                           style="width: 80px;">
                                </td>
                                <td>R$ ${subtotal.toFixed(2)}</td>
                                <td>
                                    <button onclick="carrinho.removerItem(${produto.id})" 
                                            class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    tbody.innerHTML = html;
                    document.getElementById('total-carrinho').innerHTML = 
                        `<strong>R$ ${total.toFixed(2)}</strong>`;
                })
                .catch(error => {
                    console.error('Erro ao buscar produtos:', error);
                    carrinhoVazio.style.display = 'block';
                    carrinhoItens.style.display = 'none';
                    alert('Erro ao carregar produtos do carrinho: ' + error.message);
                });
        }

        // Atualizar interface quando o carrinho mudar
        carrinho.onChange = atualizarInterfaceCarrinho;
        
        // Atualizar interface inicial
        atualizarInterfaceCarrinho();

        // Função para finalizar compra
        function finalizarCompra() {
            // Aqui você pode implementar a lógica de finalização da compra
            alert('Funcionalidade em desenvolvimento!');
        }
    </script>
</body>
</html> 