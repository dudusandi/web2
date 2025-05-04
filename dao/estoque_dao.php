<?php
require_once '../config/database.php';
require_once '../model/estoque.php';

class EstoqueDAO {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function inserir(Estoque $estoque) {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO estoques (quantidade, preco) 
                    VALUES (:quantidade, :preco) 
                    RETURNING id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':quantidade' => $estoque->getQuantidade(),
                ':preco' => $estoque->getPreco()
            ]);

            $estoqueId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            $estoque->setId($estoqueId);

            $this->pdo->commit();
            return $estoqueId;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao inserir estoque: " . $e->getMessage());
        }
    }

    public function buscarPorId($id) {
        $sql = "SELECT id, quantidade, preco 
                FROM estoques 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);
            $estoque = new Estoque(
                $linha['quantidade'],
                $linha['preco']
            );
            $estoque->setId($linha['id']);
            return $estoque;
        }

        return null;
    }

    public function atualizar(Estoque $estoque) {
        try {
            $sql = "UPDATE estoques 
                    SET quantidade = :quantidade, preco = :preco 
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':quantidade' => $estoque->getQuantidade(),
                ':preco' => $estoque->getPreco(),
                ':id' => $estoque->getId()
            ]);

            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao atualizar estoque: " . $e->getMessage());
        }
    }

    public function remover($id) {
        try {
            $sql = "DELETE FROM estoques WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao remover estoque: " . $e->getMessage());
        }
    }
}
?>