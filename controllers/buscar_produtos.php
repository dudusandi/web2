<?php
// Desativa a saída de erros para o navegador
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Limpa qualquer saída anterior
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

session_start();
require_once '../config/database.php';
require_once '../dao/produto_dao.php';
require_once '../model/produto.php';

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json; charset=utf-8');

try {
    error_log("Iniciando busca de produtos...");
    
    $pdo = Database::getConnection();
    $produtoDAO = new ProdutoDAO($pdo);
    
    // Verifica se há IDs específicos na requisição
    if (isset($_GET['ids'])) {
        error_log("Buscando produtos por IDs: " . $_GET['ids']);
        $ids = array_map('intval', explode(',', $_GET['ids']));
        $produtos = $produtoDAO->buscarProdutosPorIds($ids);
    } else {
        $termo = $_GET['termo'] ?? '';
        error_log("Buscando produtos por termo: " . $termo);
        $produtos = $produtoDAO->buscarProdutos($termo);
    }

    error_log("Produtos encontrados: " . count($produtos));

    $produtosArray = [];
    if (isset($_GET['ids'])) {
        // Se buscou por IDs, $produtos é um array de OBJETOS Produto
        foreach ($produtos as $produto) { // $produto é um objeto Produto
            try {
                $foto = $produto->getFoto();
                if ($foto && is_resource($foto)) {
                    $foto = stream_get_contents($foto);
                }

                $produtosArray[] = [
                    'id' => $produto->getId(),
                    'nome' => $produto->getNome(),
                    'descricao' => $produto->getDescricao(),
                    'foto' => $foto ? base64_encode($foto) : null,
                    'fornecedor_id' => $produto->getFornecedorId(),
                    'quantidade' => $produto->getQuantidade(),
                    'preco' => $produto->getPreco(),
                    'fornecedor_nome' => $produto->fornecedor_nome // Propriedade pública definida no DAO
                ];
            } catch (Exception $e) {
                error_log("Erro ao processar produto (objeto): " . $e->getMessage());
                continue;
            }
        }
    } else {
        // Se buscou por termo, $produtos é um array de ARRAYS associativos do ProdutoDAO->buscarProdutos()
        foreach ($produtos as $produtoData) { // $produtoData é um array
            try {
                $produtosArray[] = [
                    'id' => $produtoData['id'],
                    'nome' => $produtoData['nome'],
                    'descricao' => $produtoData['descricao'],
                    // 'foto' em $produtoData já é uma string (stream_get_contents feito no DAO)
                    'foto' => $produtoData['foto'] ? base64_encode($produtoData['foto']) : null,
                    'fornecedor_id' => $produtoData['fornecedor_id'],
                    'quantidade' => $produtoData['quantidade'],
                    'preco' => $produtoData['preco'],
                    'fornecedor_nome' => $produtoData['fornecedor_nome']
                ];
            } catch (Exception $e) {
                error_log("Erro ao processar produto (array): " . $e->getMessage());
                continue;
            }
        }
    }

    $response = [
        'success' => true,
        'produtos' => $produtosArray
    ];

    // Limpa qualquer saída anterior
    ob_clean();
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Erro em buscar_produtos.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Limpa qualquer saída anterior
    ob_clean();
    
    $response = [
        'success' => false,
        'error' => 'Erro ao buscar produtos: ' . $e->getMessage()
    ];
    
    echo json_encode($response);
}

// Envia a saída e limpa o buffer
ob_end_flush(); 