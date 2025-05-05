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
            $produto->setQuantidade($quantidade); // Preenche os novos atributos
            $produto->setPreco($preco);

            $this->pdo->commit();
            error_log(date('[Y-m-d H:i:s] ') . "Produto inserido com sucesso, ID: $produtoId" . PHP_EOL);
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log(date('[Y-m-d H:i:s] ') . "Erro ao cadastrar produto: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function listarTodosProdutos($itensPorPagina, $offset) {
        try {
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.fornecedor_id, p.estoque_id, p.usuario_id,
                           e.quantidade, e.preco,
                           f.nome AS fornecedor_nome
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
                    ORDER BY p.id DESC
                    LIMIT :itensPorPagina OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':itensPorPagina', $itensPorPagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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
                $produto->setQuantidade($linha['quantidade']);
                $produto->setPreco($linha['preco']);
                $produto->fornecedor_nome = $linha['fornecedor_nome'] ?? 'Sem fornecedor';
                $produtos[] = $produto;
            }
            return $produtos;
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em listarTodosProdutos: " . $e->getMessage() . PHP_EOL);
            throw $e;
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
                $produto->setQuantidade($linha['quantidade']); // Preenche os novos atributos
                $produto->setPreco($linha['preco']);
                $produtos[] = $produto;
            }
            return $produtos;
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em listarTodos: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function contarTodosProdutos() {
        try {
            $sql = "SELECT COUNT(*) FROM produtos";
            $stmt = $this->pdo->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em contarTodosProdutos: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function excluir($id) {
        try {
            $sql = "DELETE FROM produtos WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro ao excluir produto: " . $e->getMessage() . PHP_EOL);
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
                           e.quantidade, e.preco,
                           f.nome AS fornecedor_nome
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
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
                $produto->setQuantidade($linha['quantidade']);
                $produto->setPreco($linha['preco']);
                $produto->fornecedor_nome = $linha['fornecedor_nome'] ?? 'Sem fornecedor';
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
                           e.quantidade, e.preco,
                           f.nome AS fornecedor_nome
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
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
                $produto->setQuantidade($linha['quantidade']);
                $produto->setPreco($linha['preco']);
                $produto->fornecedor_nome = $linha['fornecedor_nome'] ?? 'Sem fornecedor'; // Preenche o fornecedor_nome
                error_log("buscarPorId - Produto ID: {$linha['id']}, Fornecedor Nome: " . ($linha['fornecedor_nome'] ?? 'Sem fornecedor'));
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
                $produto->setQuantidade($linha['quantidade']); // Preenche os novos atributos
                $produto->setPreco($linha['preco']);
                return $produto;
            }
            return null;
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em buscarPorNome: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function atualizarProduto(Produto $produto) {
        try {
            $this->pdo->beginTransaction();

            // Atualiza o estoque associado
            $estoqueId = $produto->getEstoqueId();
            if ($estoqueId) {
                $estoque = new Estoque($produto->getQuantidade(), $produto->getPreco());
                $estoque->setId($estoqueId);
                $this->estoqueDAO->atualizar($estoque);
            }

            // Atualiza o produto
            $sql = "UPDATE produtos SET nome = :nome, descricao = :descricao, foto = :foto, fornecedor_id = :fornecedor_id 
                    WHERE id = :id AND usuario_id = :usuario_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $produto->getNome(), PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $produto->getDescricao() ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':foto', $produto->getFoto() ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':fornecedor_id', $produto->getFornecedorId(), PDO::PARAM_INT);
            $stmt->bindValue(':id', $produto->getId(), PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $produto->getUsuarioId(), PDO::PARAM_INT);
            $success = $stmt->execute();

            $this->pdo->commit();
            return $success;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log(date('[Y-m-d H:i:s] ') . "Erro ao atualizar produto: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    public function removerProduto($id) {
        try {
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
    
                return true;
            }
            throw new Exception("Produto não encontrado");
        } catch (Exception $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em removerProduto: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }
}
?>