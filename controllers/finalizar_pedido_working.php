<?php
// Este arquivo usa a abordagem mais simples que sabemos que funciona,
// mas implementa a funcionalidade real de finalizar pedidos

// Desativar todas as configurações que possam interferir
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forçar cabeçalho de resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo '{"success":false,"message":"Usuário não está logado"}';
    exit;
}

// Carregar dependências necessárias
require_once '../config/database.php';
require_once '../dao/produto_dao.php';
require_once '../controllers/carrinho.php';

try {
    // Obter produtos do carrinho
    $produtosCarrinho = obterProdutosCarrinho();
    
    // Verificar se o carrinho está vazio
    if (empty($produtosCarrinho)) {
        echo '{"success":false,"message":"Seu carrinho está vazio"}';
        exit;
    }
    
    // Conectar ao banco de dados
    $pdo = Database::getConnection();
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Criar pedido
    $clienteId = $_SESSION['usuario_id'];
    $numeroPedido = 'P' . date('ymdHis') . rand(10, 99);
    
    $stmt = $pdo->prepare("INSERT INTO pedidos (numero, cliente_id, data_pedido, situacao) VALUES (:numero, :cliente_id, NOW(), 'NOVO')");
    $stmt->bindParam(':numero', $numeroPedido);
    $stmt->bindParam(':cliente_id', $clienteId);
    $stmt->execute();
    
    // Obter ID do pedido
    $pedidoId = $pdo->lastInsertId('pedidos_id_seq');
    
    if (!$pedidoId) {
        $stmt = $pdo->prepare("SELECT id FROM pedidos WHERE numero = :numero");
        $stmt->bindParam(':numero', $numeroPedido);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && isset($resultado['id'])) {
            $pedidoId = $resultado['id'];
        } else {
            throw new Exception("Não foi possível obter o ID do pedido criado");
        }
    }
    
    // Inserir itens do pedido
    $valorTotal = 0;
    
    foreach ($produtosCarrinho as $produto) {
        $produtoId = $produto['id'];
        $quantidade = $produto['quantidade'];
        $precoUnitario = $produto['preco'];
        $subtotal = $produto['subtotal'];
        
        $stmt = $pdo->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario, :subtotal)");
        $stmt->bindParam(':pedido_id', $pedidoId);
        $stmt->bindParam(':produto_id', $produtoId);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':preco_unitario', $precoUnitario);
        $stmt->bindParam(':subtotal', $subtotal);
        $stmt->execute();
        
        $valorTotal += $subtotal;
    }
    
    // Atualizar valor total do pedido
    $stmt = $pdo->prepare("UPDATE pedidos SET valor_total = :valor_total WHERE id = :pedido_id");
    $stmt->bindParam(':valor_total', $valorTotal);
    $stmt->bindParam(':pedido_id', $pedidoId);
    $stmt->execute();
    
    // Confirmar transação
    $pdo->commit();
    
    // Limpar o carrinho
    limparCarrinho();
    
    // Formatar valor
    $valorFormatado = number_format($valorTotal, 2, ',', '.');
    
    // Enviar resposta de sucesso
    echo '{"success":true,"mensagem":"Pedido finalizado com sucesso!","pedido":{"id":'.$pedidoId.',"numero":"'.$numeroPedido.'","valor_total":"'.$valorFormatado.'"}}';
    
} catch (Exception $e) {
    // Reverter a transação em caso de erro
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Enviar resposta de erro
    echo '{"success":false,"message":"Erro ao finalizar pedido: '.$e->getMessage().'"}';
}
?> 