<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/produto.php';

class ProdutoDAO {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function cadastrarProduto(Produto $produto) {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO produtos 
                    (nome, descricao, foto, fornecedor, estoque, usuario_id) 
                    VALUES (:nome, :descricao, :foto, :fornecedor, :estoque, :usuario_id)";
            $stmt = $this->pdo->prepare($sql);

            $nome = $produto->getNome() ?? '';
            $descricao = $produto->getDescricao();
            $foto = $produto->getFoto();
            $fornecedor = $produto->getFornecedor() ?? '';
            $estoque = (int)($produto->getEstoque() ?? 0);
            $usuario_id = (int)($produto->getUsuarioId() ?? 0);

            if (empty($nome) || empty($fornecedor)) {
                throw new Exception("Nome e fornecedor são obrigatórios");
            }
            if ($usuario_id === 0) {
                throw new Exception("ID do usuário é obrigatório");
            }

            error_log(date('[Y-m-d H:i:s] ') . "Executando query: $sql com valores: " . print_r([
                'nome' => $nome,
                'descricao' => $descricao,
                'foto' => $foto,
                'fornecedor' => $fornecedor,
                'estoque' => $estoque,
                'usuario_id' => $usuario_id
            ], true) . PHP_EOL);

            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao,
                ':foto' => $foto,
                ':fornecedor' => $fornecedor,
                ':estoque' => $estoque,
                ':usuario_id' => $usuario_id
            ]);

            $this->pdo->commit();
            error_log(date('[Y-m-d H:i:s] ') . "Produto inserido com sucesso" . PHP_EOL);
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log(date('[Y-m-d H:i:s] ') . "Erro ao cadastrar produto: SQLSTATE[{$e->getCode()}]: " . $e->getMessage() . PHP_EOL);
            throw new Exception("Erro ao cadastrar produto: " . $e->getMessage());
        }
    }

    public function nomeExiste($nome, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM produtos WHERE LOWER(nome) = LOWER(:nome)";
            if ($excludeId) {
                $sql .= " AND id != :excludeId";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            if ($excludeId) {
                $stmt->bindValue(':excludeId', $excludeId, PDO::PARAM_INT);
            }
            $stmt->execute();
            $exists = $stmt->fetchColumn() > 0;
            error_log(date('[Y-m-d H:i:s] ') . "Verificando nome: $nome, existe: " . ($exists ? 'sim' : 'não') . PHP_EOL);
            return $exists;
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em nomeExiste: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function listarTodos($limit = null, $offset = null) {
        try {
            $sql = "SELECT id, nome, descricao, foto, fornecedor, estoque, usuario_id FROM produtos";
            if ($limit !== null && $offset !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            $stmt = $this->pdo->prepare($sql);
            if ($limit !== null && $offset !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            $stmt->execute();

            $produtos = [];
            while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produto = new Produto(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['foto'],
                    $linha['fornecedor'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoque($linha['estoque']);
                $produtos[] = $produto;
            }
            return $produtos;
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em listarTodos: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function contarProdutosPorUsuario($usuario_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM produtos WHERE usuario_id = :usuario_id");
            $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em contarProdutosPorUsuario: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function listarProdutosPorUsuario($usuario_id, $limit = null, $offset = null) {
        try {
            $sql = "SELECT id, nome, descricao, foto, fornecedor, estoque, usuario_id 
                    FROM produtos 
                    WHERE usuario_id = :usuario_id";
            if ($limit !== null && $offset !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
            if ($limit !== null && $offset !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            $stmt->execute();
    
            $produtos = [];
            while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produto = new Produto(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['foto'],
                    $linha['fornecedor'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoque($linha['estoque']);
                $produtos[] = $produto;
            }
            return $produtos;
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em listarProdutosPorUsuario: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function contarProdutos() {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM produtos");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em contarProdutos: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function buscarPorId($id) {
        try {
            $sql = "SELECT id, nome, descricao, foto, fornecedor, estoque, usuario_id 
                    FROM produtos 
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $linha = $stmt->fetch(PDO::FETCH_ASSOC);
                $produto = new Produto(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['foto'],
                    $linha['fornecedor'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoque($linha['estoque']);
                return $produto;
            }
            return null;
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em buscarPorId: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function buscarPorNome($nome) {
        try {
            $sql = "SELECT id, nome, descricao, foto, fornecedor, estoque, usuario_id 
                    FROM produtos 
                    WHERE LOWER(nome) = LOWER(:nome)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $linha = $stmt->fetch(PDO::FETCH_ASSOC);
                $produto = new Produto(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['foto'],
                    $linha['fornecedor'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoque($linha['estoque']);
                return $produto;
            }
            return null;
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em buscarPorNome: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function atualizarProduto(Produto $produto, $id) {
        try {
            $this->pdo->beginTransaction();
    
            if (empty($produto->getNome()) || empty($produto->getFornecedor())) {
                throw new Exception("Nome e fornecedor são obrigatórios");
            }
    
            $sql = "UPDATE produtos SET 
                    nome = :nome, 
                    descricao = :descricao, 
                    foto = :foto, 
                    fornecedor = :fornecedor, 
                    estoque = :estoque,
                    usuario_id = :usuario_id
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $produto->getNome(),
                ':descricao' => $produto->getDescricao(),
                ':foto' => $produto->getFoto(),
                ':fornecedor' => $produto->getFornecedor(),
                ':estoque' => (int)($produto->getEstoque() ?? 0),
                ':usuario_id' => $produto->getUsuarioId(),
                ':id' => $id
            ]);
    
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log(date('[Y-m-d H:i:s] ') . "Erro em atualizarProduto: SQLSTATE[{$e->getCode()}]: " . $e->getMessage() . PHP_EOL);
            throw new Exception("Erro ao atualizar produto: " . $e->getMessage());
        }
    }

    public function removerProduto($id) {
        try {
            $this->pdo->beginTransaction();

            $produto = $this->buscarPorId($id);
            if ($produto && $produto->getFoto()) {
                $caminhoFoto = __DIR__ . '/../../public' . $produto->getFoto();
                if (file_exists($caminhoFoto)) {
                    unlink($caminhoFoto);
                    error_log(date('[Y-m-d H:i:s] ') . "Foto removida: $caminhoFoto" . PHP_EOL);
                }
            }

            $sql = "DELETE FROM produtos WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log(date('[Y-m-d H:i:s] ') . "Erro em removerProduto: " . $e->getMessage() . PHP_EOL);
            throw new Exception("Erro ao remover produto: " . $e->getMessage());
        }
    }
}
?>