<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Pedido.php';
require_once __DIR__ . '/../model/item_pedido.php';
require_once __DIR__ . '/../dao/cliente_dao.php';
require_once __DIR__ . '/../dao/produto_dao.php';

class PedidoDAO {
    private $pdo;
    private $clienteDAO;
    private $produtoDAO;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->clienteDAO = new ClienteDAO($pdo);
        $this->produtoDAO = new ProdutoDAO($pdo);
    }

    /**
     * Cria um novo pedido no banco de dados
     */
    public function criarPedido($clienteId, $itensPedido) {
        try {
            // Validar itens do pedido
            if (empty($itensPedido) || !is_array($itensPedido)) {
                throw new Exception("Nenhum item válido para o pedido");
            }

            // Gerar número único para o pedido com no máximo 20 caracteres
            $numero = 'P' . date('ymdHis') . rand(10, 99);
            
            $this->pdo->beginTransaction();

            // Inserir o pedido - Versão sem RETURNING
            $sqlPedido = "INSERT INTO pedidos (numero, cliente_id, data_pedido, situacao) 
                          VALUES (:numero, :cliente_id, NOW(), 'NOVO')";
            $stmtPedido = $this->pdo->prepare($sqlPedido);
            $stmtPedido->bindParam(':numero', $numero);
            $stmtPedido->bindParam(':cliente_id', $clienteId);
            $stmtPedido->execute();
            
            // Obter o ID do pedido inserido
            $pedidoId = $this->pdo->lastInsertId('pedidos_id_seq');
            
            if (!$pedidoId) {
                // Alternativa: buscar ID pelo número do pedido
                $sqlId = "SELECT id FROM pedidos WHERE numero = :numero";
                $stmtId = $this->pdo->prepare($sqlId);
                $stmtId->bindParam(':numero', $numero);
                $stmtId->execute();
                $pedidoResult = $stmtId->fetch(PDO::FETCH_ASSOC);
                
                if (!$pedidoResult || !isset($pedidoResult['id'])) {
                    throw new Exception("Falha ao obter ID do pedido criado");
                }
                
                $pedidoId = $pedidoResult['id'];
            }
            
            $valorTotal = 0;
            
            // Inserir os itens do pedido
            $sqlItem = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal) 
                        VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario, :subtotal)";
            $stmtItem = $this->pdo->prepare($sqlItem);
            
            foreach ($itensPedido as $item) {
                if (!isset($item['id']) || !isset($item['quantidade']) || !isset($item['preco'])) {
                    continue;
                }
                
                $produtoId = (int)$item['id'];
                $quantidade = (int)$item['quantidade'];
                $precoUnitario = (float)$item['preco'];
                $subtotal = $precoUnitario * $quantidade;
                
                $stmtItem->bindParam(':pedido_id', $pedidoId);
                $stmtItem->bindParam(':produto_id', $produtoId);
                $stmtItem->bindParam(':quantidade', $quantidade);
                $stmtItem->bindParam(':preco_unitario', $precoUnitario);
                $stmtItem->bindParam(':subtotal', $subtotal);
                $stmtItem->execute();
                
                $valorTotal += $subtotal;
            }
            
            // Atualizar o valor total do pedido
            $sqlTotal = "UPDATE pedidos SET valor_total = :valor_total WHERE id = :pedido_id";
            $stmtTotal = $this->pdo->prepare($sqlTotal);
            $stmtTotal->bindParam(':valor_total', $valorTotal);
            $stmtTotal->bindParam(':pedido_id', $pedidoId);
            $stmtTotal->execute();
            
            $this->pdo->commit();
            
            return [
                'id' => $pedidoId,
                'numero' => $numero,
                'valor_total' => $valorTotal
            ];
        } catch (Exception $e) {
            if ($this->pdo && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Busca um pedido pelo ID
     */
    public function buscarPorId($pedidoId) {
        try {
            $sql = "SELECT p.id, p.numero, p.data_pedido, p.data_entrega, p.situacao, 
                           p.cliente_id, p.valor_total
                    FROM pedidos p
                    WHERE p.id = :pedido_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':pedido_id', $pedidoId);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cliente = $this->clienteDAO->buscarPorId($row['cliente_id']);
                
                $pedido = new Pedido(
                    $row['numero'],
                    $row['data_pedido'],
                    $cliente,
                    $row['data_entrega'],
                    $row['situacao']
                );
                
                $this->carregarItensPedido($pedido, $pedidoId);
                
                return $pedido;
            }
            
            return null;
        } catch (Exception $e) {
            error_log('Erro ao buscar pedido: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Carrega os itens de um pedido
     */
    private function carregarItensPedido($pedido, $pedidoId) {
        $sql = "SELECT ip.id, ip.produto_id, ip.quantidade, ip.preco_unitario, ip.subtotal
                FROM itens_pedido ip
                WHERE ip.pedido_id = :pedido_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':pedido_id', $pedidoId);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $produto = $this->produtoDAO->buscarPorId($row['produto_id']);
            
            $itemPedido = new ItemPedido(
                $row['quantidade'],
                $row['preco_unitario'],
                $produto,
                $pedido
            );
            
            $pedido->getItensPedido()[] = $itemPedido;
        }
    }
    
    /**
     * Lista os pedidos de um cliente
     */
    public function listarPedidosCliente($clienteId) {
        try {
            $sql = "SELECT p.id, p.numero, p.data_pedido, p.data_entrega, p.situacao, 
                           p.cliente_id, p.valor_total
                    FROM pedidos p
                    WHERE p.cliente_id = :cliente_id
                    ORDER BY p.data_pedido DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':cliente_id', $clienteId);
            $stmt->execute();
            
            $pedidos = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pedidos[] = [
                    'id' => $row['id'],
                    'numero' => $row['numero'],
                    'data_pedido' => $row['data_pedido'],
                    'data_entrega' => $row['data_entrega'],
                    'situacao' => $row['situacao'],
                    'valor_total' => $row['valor_total']
                ];
            }
            
            return $pedidos;
        } catch (Exception $e) {
            error_log('Erro ao listar pedidos: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Atualiza a situação de um pedido
     */
    public function atualizarSituacao($pedidoId, $situacao) {
        try {
            $sql = "UPDATE pedidos SET situacao = :situacao WHERE id = :pedido_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':situacao', $situacao);
            $stmt->bindParam(':pedido_id', $pedidoId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Erro ao atualizar situação: ' . $e->getMessage());
            throw $e;
        }
    }
}
?> 