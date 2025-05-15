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
            error_log("DEBUG (PedidoDAO): Iniciando buscarPorId para pedidoId: " . $pedidoId);
            $sql = "SELECT p.id, p.numero, p.data_pedido, p.data_entrega, p.situacao, 
                           p.cliente_id, p.valor_total
                    FROM pedidos p
                    WHERE p.id = :pedido_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':pedido_id', $pedidoId);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                error_log("DEBUG (PedidoDAO): Pedido encontrado no banco: " . print_r($row, true));
                $cliente = $this->clienteDAO->buscarPorId($row['cliente_id']);
                
                if(!$cliente){
                    error_log("AVISO (PedidoDAO): Cliente com ID {$row['cliente_id']} não encontrado para o pedido {$pedidoId}.");
                    // Decide como lidar: talvez lançar exceção ou retornar pedido sem cliente?
                    // Por ora, continuaremos, mas isso é um problema potencial.
                }
                error_log("DEBUG (PedidoDAO): Cliente associado ao pedido: " . ($cliente ? $cliente->getNome() : 'NÃO ENCONTRADO'));

                $pedido = new Pedido(
                    $row['numero'],
                    $row['data_pedido'],
                    $cliente, // Pode ser null se o cliente não for encontrado
                    $row['data_entrega'],
                    $row['situacao']
                );
                // Definir o ID do pedido no objeto Pedido, se sua classe Pedido tiver um setId().
                // Se não, você pode precisar adicionar ou apenas usar o ID do $row onde necessário.
                // Ex: $pedido->setId($row['id']); 

                error_log("DEBUG (PedidoDAO): Objeto Pedido criado. Chamando carregarItensPedido.");
                $this->carregarItensPedido($pedido, $pedidoId);
                error_log("DEBUG (PedidoDAO): Itens do pedido após carregarItensPedido (dentro de buscarPorId): " . count($pedido->getItensPedido()) . " itens. Conteúdo: " . print_r($pedido->getItensPedido(), true));
                
                return $pedido;
            }
            
            error_log("DEBUG (PedidoDAO): Pedido com ID {$pedidoId} não encontrado em buscarPorId.");
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
        error_log("DEBUG (PedidoDAO): Entrando em carregarItensPedido para pedidoId: " . $pedidoId);
        $sql = "SELECT ip.id, ip.produto_id, ip.quantidade, ip.preco_unitario, ip.subtotal
                FROM itens_pedido ip
                WHERE ip.pedido_id = :pedido_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':pedido_id', $pedidoId);
        $stmt->execute();
        error_log("DEBUG (PedidoDAO): Query de itens executada. RowCount: " . $stmt->rowCount());
        
        $itensAdicionados = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            error_log("DEBUG (PedidoDAO): Processando item do BD: " . print_r($row, true));
            $produto = $this->produtoDAO->buscarPorId($row['produto_id']);
            error_log("DEBUG (PedidoDAO): Produto buscado para item (Produto ID: {$row['produto_id']}): " . ($produto ? "Encontrado - " . $produto->getNome() : 'NÃO ENCONTRADO'));
            
            if ($produto) {
                $itemPedido = new ItemPedido(
                    $row['quantidade'],
                    $row['preco_unitario'],
                    $produto,
                    $pedido // Passando o objeto Pedido que está sendo construído
                );
                // Adicionar item ao pedido. 
                // Se Pedido::getItensPedido() retorna o array por referência, isso funciona.
                // Alternativamente, Pedido poderia ter um método addItem(ItemPedido $item).
                $pedido->getItensPedido()[] = $itemPedido; 
                $itensAdicionados++;
                error_log("DEBUG (PedidoDAO): ItemPedido criado e adicionado ao objeto Pedido: " . print_r($itemPedido, true));
            } else {
                error_log("AVISO (PedidoDAO): Produto com ID {$row['produto_id']} não encontrado para o item do pedido {$pedidoId}. Item não será adicionado.");
            }
        }
        error_log("DEBUG (PedidoDAO): Saindo de carregarItensPedido. Total de itens efetivamente adicionados nesta chamada: " . $itensAdicionados . ". Total no objeto pedido agora: " . count($pedido->getItensPedido()));
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

    /**
     * Lista todos os pedidos do sistema (para admin)
     */
    public function listarTodosPedidos($pagina = 1, $itensPorPagina = 10) {
        try {
            $offset = ($pagina - 1) * $itensPorPagina;

            $sql = "SELECT p.id, p.numero, p.data_pedido, p.situacao, p.valor_total, 
                           c.nome as nome_cliente, c.email as email_cliente
                    FROM pedidos p
                    JOIN clientes c ON p.cliente_id = c.id
                    ORDER BY p.data_pedido DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar o total de pedidos para paginação
            $sqlTotal = "SELECT COUNT(*) FROM pedidos";
            $totalPedidos = $this->pdo->query($sqlTotal)->fetchColumn();
            
            return [
                'pedidos' => $pedidos,
                'total' => (int)$totalPedidos,
                'pagina' => $pagina,
                'itensPorPagina' => $itensPorPagina
            ];
            
        } catch (Exception $e) {
            error_log('Erro ao listar todos os pedidos: ' . $e->getMessage());
            throw $e;
        }
    }
}
?> 