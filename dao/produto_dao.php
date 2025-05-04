<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/produto.php';
require_once __DIR__ . '/../dao/estoque_dao.php';

class ProdutoDAO {
    private $pdo;
    private $estoqueDAO;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->estoqueDAO = new EstoqueDAO($pdo);
    }

    public function cadastrarProduto(Produto $produto, $quantidade, $preco) {
        try {
            $this->pdo->beginTransaction();

            // Validações
            $nome = $produto->getNome() ?? '';
            $fornecedorId = (int)($produto->getFornecedorId() ?? 0);
            $usuarioId = (int)($produto->getUsuarioId() ?? 0);

            if (empty($nome) || $fornecedorId <= 0) {
                throw new Exception("Nome e fornecedor são obrigatórios");
            }
            if ($usuarioId === 0) {
                throw new Exception("ID do usuário é obrigatório");
            }

            // Insere o estoque
            $estoque = new Estoque($quantidade, $preco);
            $estoqueId = $this->estoqueDAO->inserir($estoque);

            // Insere o produto
            $sql = "INSERT INTO produtos 
                    (nome, descricao, foto, fornecedor_id, estoque_id, usuario_id) 
                    VALUES (:nome, :descricao, :foto, :fornecedor_id, :estoque_id, :usuario_id)
                    RETURNING id";
            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':nome' => $produto->getNome(),
                ':descricao' => $produto->getDescricao(),
                ':foto' => $produto->getFoto(),
                ':fornecedor_id' => $fornecedorId,
                ':estoque_id' => $estoqueId,
                ':usuario_id' => $usuarioId
            ]);

            $produtoId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            $produto->setId($produtoId);
            $produto->setEstoqueId($estoqueId);

            $this->pdo->commit();
            error_log(date('[Y-m-d H:i:s] ') . "Produto inserido com sucesso, ID: $produtoId" . PHP_EOL);
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
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.fornecedor_id, p.estoque_id, p.usuario_id,
                           e.quantidade, e.preco
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id";
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
                    $linha['fornecedor_id'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoqueId($linha['estoque_id']);
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
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.fornecedor_id, p.estoque_id, p.usuario_id,
                           e.quantidade, e.preco
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    WHERE p.usuario_id = :usuario_id";
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
                    $linha['fornecedor_id'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoqueId($linha['estoque_id']);
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
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.fornecedor_id, p.estoque_id, p.usuario_id,
                           e.quantidade, e.preco
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    WHERE p.id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $linha = $stmt->fetch(PDO::FETCH_ASSOC);
                $produto = new Produto(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['foto'],
                    $linha['fornecedor_id'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoqueId($linha['estoque_id']);
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
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.fornecedor_id, p.estoque_id, p.usuario_id,
                           e.quantidade, e.preco
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    WHERE LOWER(p.nome) = LOWER(:nome)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $linha = $stmt->fetch(PDO::FETCH_ASSOC);
                $produto = new Produto(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['foto'],
                    $linha['fornecedor_id'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoqueId($linha['estoque_id']);
                return $produto;
            }
            return null;
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em buscarPorNome: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function atualizarProduto(Produto $produto, $quantidade, $preco) {
        try {
            $this->pdo->beginTransaction();

            if (empty($produto->getNome()) || $produto->getFornecedorId() <= 0) {
                throw new Exception("Nome e fornecedor são obrigatórios");
            }

            // Atualiza o estoque associado
            $estoqueId = $produto->getEstoqueId();
            $estoque = $this->estoqueDAO->buscarPorId($estoqueId);
            if ($estoque) {
                $estoque->setQuantidade($quantidade);
                $estoque->setPreco($preco);
                $this->estoqueDAO->atualizar($estoque);
            } else {
                throw new Exception("Estoque não encontrado para o produto");
            }

            // Atualiza o produto
            $sql = "UPDATE produtos SET 
                    nome = :nome, 
                    descricao = :descricao, 
                    foto = :foto, 
                    fornecedor_id = :fornecedor_id, 
                    usuario_id = :usuario_id
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $produto->getNome(),
                ':descricao' => $produto->getDescricao(),
                ':foto' => $produto->getFoto(),
                ':fornecedor_id' => $produto->getFornecedorId(),
                ':usuario_id' => $produto->getUsuarioId(),
                ':id' => $produto->getId()
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
            if ($produto) {
                // Remove a foto, se existir
                if ($produto->getFoto()) {
                    $caminhoFoto = __DIR__ . '/../../public/uploads/imagens/' . $produto->getFoto();
                    if (file_exists($caminhoFoto)) {
                        unlink($caminhoFoto);
                        error_log(date('[Y-m-d H:i:s] ') . "Foto removida: $caminhoFoto" . PHP_EOL);
                    }
                }

                // Remove o estoque associado
                $estoqueId = $produto->getEstoqueId();
                if ($estoqueId) {
                    $this->estoqueDAO->remover($estoqueId);
                }

                // Remove o produto
                $sql = "DELETE FROM produtos WHERE id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                $this->pdo->commit();
                return true;
            }
            throw new Exception("Produto não encontrado");
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log(date('[Y-m-d H:i:s] ') . "Erro em removerProduto: " . $e->getMessage() . PHP_EOL);
            throw new Exception("Erro ao remover produto: " . $e->getMessage());
        }
    }
}
?>