<?php
$basePath = realpath(dirname(__DIR__));
require_once "$basePath/config/database.php";
require_once "$basePath/model/produto.php";

class ProdutoDAO {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function cadastrarProduto(Produto $produto) {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO produtos 
                    (nome, descricao, foto, fornecedor, estoque) 
                    VALUES (:nome, :descricao, :foto, :fornecedor, :estoque)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $produto->getNome(),
                ':descricao' => $produto->getDescricao(),
                ':foto' => $produto->getFoto(),
                ':fornecedor' => $produto->getFornecedor(),
                ':estoque' => $produto->getEstoque() ?? 0
            ]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao cadastrar produto: " . $e->getMessage());
        }
    }

    public function buscarPorId($id) {
        $sql = "SELECT id, nome, descricao, foto, fornecedor, estoque 
                FROM produtos 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);

            $produto = new Produto(
                $linha['nome'],
                $linha['descricao'],
                $linha['foto'],
                $linha['fornecedor']
            );
            $produto->setEstoque($linha['estoque']);
            return $produto;
        }

        return null;
    }

    public function buscarPorNome($nome) {
        $sql = "SELECT id, nome, descricao, foto, fornecedor, estoque 
                FROM produtos 
                WHERE nome = :nome";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":nome", $nome);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);

            $produto = new Produto(
                $linha['nome'],
                $linha['descricao'],
                $linha['foto'],
                $linha['fornecedor']
            );
            $produto->setEstoque($linha['estoque']);
            return $produto;
        }

        return null;
    }

    public function listarTodos() {
        $sql = "SELECT id, nome, descricao, foto, fornecedor, estoque 
                FROM produtos";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $produtos = [];

        while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $produto = new Produto(
                $linha['nome'],
                $linha['descricao'],
                $linha['foto'],
                $linha['fornecedor']
            );
            $produto->setEstoque($linha['estoque']);
            $produtos[] = $produto;
        }

        return $produtos;
    }

    public function atualizarProduto(Produto $produto, $id) {
        try {
            $this->pdo->beginTransaction();

            $sql = "UPDATE produtos SET 
                    nome = :nome, 
                    descricao = :descricao, 
                    foto = :foto, 
                    fornecedor = :fornecedor, 
                    estoque = :estoque 
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $produto->getNome(),
                ':descricao' => $produto->getDescricao(),
                ':foto' => $produto->getFoto(),
                ':fornecedor' => $produto->getFornecedor(),
                ':estoque' => $produto->getEstoque() ?? 0,
                ':id' => $id
            ]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao atualizar produto: " . $e->getMessage());
        }
    }

    public function removerProduto($id) {
        try {
            $this->pdo->beginTransaction();

            // Buscar o caminho da foto para removê-la do sistema de arquivos
            $produto = $this->buscarPorId($id);
            if ($produto && $produto->getFoto()) {
                global $basePath;
                $caminhoFoto = $basePath . '/public' . $produto->getFoto();
                if (file_exists($caminhoFoto)) {
                    unlink($caminhoFoto);
                }
            }

            $sql = "DELETE FROM produtos WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao remover produto: " . $e->getMessage());
        }
    }

    public function nomeExiste($nome, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM produtos WHERE nome = :nome";
        if ($excludeId) {
            $sql .= " AND id != :excludeId";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":nome", $nome);
        if ($excludeId) {
            $stmt->bindParam(":excludeId", $excludeId);
        }
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}
?>