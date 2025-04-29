<?php
class EstoqueDAO {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function buscarPorProdutoId($produtoId) {
        $stmt = $this->pdo->prepare('SELECT quantidade FROM estoque WHERE produto_id = :produto_id');
        $stmt->execute(['produto_id' => $produtoId]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados) {
            $produto = new Produto("", "", "", "", 0); // Produto vazio apenas para associar
            $produto->setId($produtoId);
            return new Estoque($produto, $dados['quantidade']);
        }

        return null;
    }
    public function atualizarEstoque(Estoque $estoque) {
        $stmt = $this->pdo->prepare('UPDATE estoque SET quantidade = :quantidade WHERE produto_id = :produto_id');
        return $stmt->execute([
            'quantidade' => $estoque->getQuantidade(),
            'produto_id' => $estoque->getProduto()->getId()
        ]);
    }
    
    public function inserirEstoque(Estoque $estoque) {
        $stmt = $this->pdo->prepare('INSERT INTO estoque (produto_id, quantidade) VALUES (:produto_id, :quantidade)');
        return $stmt->execute([
            'produto_id' => $estoque->getProduto()->getId(),
            'quantidade' => $estoque->getQuantidade()
        ]);
    }
    
}
?>
