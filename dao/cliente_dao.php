<?php
$basePath = realpath(dirname(__DIR__));
require_once "$basePath/config/database.php";
require_once "$basePath/model/cliente.php";
require_once "$basePath/model/endereco.php";

class ClienteDAO {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function buscarPorEmailSenha($email, $senha) {
        $sql = "SELECT c.id, c.nome, c.telefone, c.email, c.cartao_credito, c.senha, 
                       e.rua, e.numero, e.bairro, e.cep, e.cidade, e.estado, e.complemento
                FROM clientes c
                JOIN enderecos e ON c.endereco_id = e.id
                WHERE c.email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar a senha diretamente
            if (password_verify($senha, $linha['senha'])) {
                $endereco = new Endereco(
                    $linha['rua'],
                    $linha['numero'],
                    $linha['bairro'],
                    $linha['cep'],
                    $linha['cidade'],
                    $linha['estado'],
                    $linha['complemento']
                );

                $cliente = new Cliente(
                    $linha['nome'],
                    $linha['telefone'],
                    $linha['email'],
                    $linha['cartao_credito'],
                    $endereco
                );
                $cliente->setId($linha['id']);
                return $cliente;
            }
        }
        return null;
    }

    public function buscarPorEmail($email) {
        $sql = "SELECT c.id, c.nome, c.telefone, c.email, c.cartao_credito, 
                       e.rua, e.numero, e.bairro, e.cep, e.cidade, e.estado, e.complemento
                FROM clientes c
                JOIN enderecos e ON c.endereco_id = e.id
                WHERE c.email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $endereco = new Endereco(
                $linha['rua'],
                $linha['numero'],
                $linha['bairro'],
                $linha['cep'],
                $linha['cidade'],
                $linha['estado'],
                $linha['complemento']
            );

            $cliente = new Cliente(
                $linha['nome'],
                $linha['telefone'],
                $linha['email'],
                $linha['cartao_credito'],
                $endereco
            );
            $cliente->setId($linha['id']);
            return $cliente;
        }
        return null;
    }

    public function cadastrarCliente(Cliente $cliente, string $senhaHash) {
        try {
            $this->pdo->beginTransaction();

            // Inserir endereço
            $endereco = $cliente->getEndereco();
            $sqlEndereco = "INSERT INTO enderecos 
                            (rua, numero, complemento, bairro, cep, cidade, estado) 
                            VALUES (:rua, :numero, :complemento, :bairro, :cep, :cidade, :estado) 
                            RETURNING id";
            $stmtEndereco = $this->pdo->prepare($sqlEndereco);
            $stmtEndereco->execute([
                ':rua' => $endereco->getRua(),
                ':numero' => $endereco->getNumero(),
                ':complemento' => $endereco->getComplemento(),
                ':bairro' => $endereco->getBairro(),
                ':cep' => $endereco->getCep(),
                ':cidade' => $endereco->getCidade(),
                ':estado' => $endereco->getEstado()
            ]);

            $enderecoId = $stmtEndereco->fetch(PDO::FETCH_ASSOC)['id'];

            // Inserir cliente
            $sqlCliente = "INSERT INTO clientes 
                          (nome, telefone, email, cartao_credito, endereco_id, senha) 
                          VALUES (:nome, :telefone, :email, :cartao_credito, :endereco_id, :senha)";
            $stmtCliente = $this->pdo->prepare($sqlCliente);
            $stmtCliente->execute([
                ':nome' => $cliente->getNome(),
                ':telefone' => $cliente->getTelefone(),
                ':email' => $cliente->getEmail(),
                ':cartao_credito' => $cliente->getCartaoCredito(),
                ':endereco_id' => $enderecoId,
                ':senha' => $senhaHash
            ]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listarTodos() {
        $sql = "SELECT c.id, c.nome, c.telefone, c.email, c.cartao_credito, 
                       e.rua, e.numero, e.bairro, e.cep, e.cidade, e.estado, e.complemento
                FROM clientes c
                JOIN enderecos e ON c.endereco_id = e.id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $clientes = array();

        while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $endereco = new Endereco(
                $linha['rua'],
                $linha['numero'],
                $linha['bairro'],
                $linha['cep'],
                $linha['cidade'],
                $linha['estado'],
                $linha['complemento']
            );

            $cliente = new Cliente(
                $linha['nome'],
                $linha['telefone'],
                $linha['email'],
                $linha['cartao_credito'],
                $endereco
            );
            $cliente->setId($linha['id']);
            $clientes[] = $cliente;
        }

        return $clientes;
    }

    public function buscarPorId($id) {
        $sql = "SELECT c.id, c.nome, c.telefone, c.email, c.cartao_credito, 
                       e.rua, e.numero, e.bairro, e.cep, e.cidade, e.estado, e.complemento
                FROM clientes c
                JOIN enderecos e ON c.endereco_id = e.id
                WHERE c.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);

            $endereco = new Endereco(
                $linha['rua'],
                $linha['numero'],
                $linha['bairro'],
                $linha['cep'],
                $linha['cidade'],
                $linha['estado'],
                $linha['complemento']
            );

            $cliente = new Cliente(
                $linha['nome'],
                $linha['telefone'],
                $linha['email'],
                $linha['cartao_credito'],
                $endereco
            );
            $cliente->setId($linha['id']);
            return $cliente;
        }

        return null;
    }

    public function atualizarCliente($cliente) {
        try {
            $this->pdo->beginTransaction();

            $endereco = $cliente->getEndereco();
            $sqlEndereco = "UPDATE enderecos SET 
                           rua = :rua, 
                           numero = :numero, 
                           complemento = :complemento, 
                           bairro = :bairro, 
                           cep = :cep, 
                           cidade = :cidade, 
                           estado = :estado
                           WHERE id = (SELECT endereco_id FROM clientes WHERE id = :clienteId)";
            $stmtEndereco = $this->pdo->prepare($sqlEndereco);
            $stmtEndereco->bindParam(":rua", $endereco->getRua());
            $stmtEndereco->bindParam(":numero", $endereco->getNumero());
            $stmtEndereco->bindParam(":complemento", $endereco->getComplemento());
            $stmtEndereco->bindParam(":bairro", $endereco->getBairro());
            $stmtEndereco->bindParam(":cep", $endereco->getCep());
            $stmtEndereco->bindParam(":cidade", $endereco->getCidade());
            $stmtEndereco->bindParam(":estado", $endereco->getEstado());
            $stmtEndereco->bindParam(":clienteId", $cliente->getId());
            $stmtEndereco->execute();

            $sqlCliente = "UPDATE clientes SET 
                           nome = :nome, 
                           telefone = :telefone, 
                           email = :email, 
                           cartao_credito = :cartao_credito
                           WHERE id = :id";
            $stmtCliente = $this->pdo->prepare($sqlCliente);
            $stmtCliente->bindParam(":nome", $cliente->getNome());
            $stmtCliente->bindParam(":telefone", $cliente->getTelefone());
            $stmtCliente->bindParam(":email", $cliente->getEmail());
            $stmtCliente->bindParam(":cartao_credito", $cliente->getCartaoCredito());
            $stmtCliente->bindParam(":id", $cliente->getId());
            $resultado = $stmtCliente->execute();

            $this->pdo->commit();
            return $resultado;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao atualizar cliente: " . $e->getMessage());
        }
    }

    public function removerCliente($id) {
        try {
            $this->pdo->beginTransaction();

            $sqlGetEndereco = "SELECT endereco_id FROM clientes WHERE id = :id";
            $stmtGet = $this->pdo->prepare($sqlGetEndereco);
            $stmtGet->bindParam(":id", $id);
            $stmtGet->execute();

            if ($stmtGet->rowCount() == 0) {
                throw new Exception("Cliente não encontrado");
            }

            $enderecoId = $stmtGet->fetch(PDO::FETCH_ASSOC)['endereco_id'];

            $sqlCliente = "DELETE FROM clientes WHERE id = :id";
            $stmtCliente = $this->pdo->prepare($sqlCliente);
            $stmtCliente->bindParam(":id", $id);
            $stmtCliente->execute();

            $sqlEndereco = "DELETE FROM enderecos WHERE id = :id";
            $stmtEndereco = $this->pdo->prepare($sqlEndereco);
            $stmtEndereco->bindParam(":id", $enderecoId);
            $stmtEndereco->execute();

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao remover cliente: " . $e->getMessage());
        }
    }

    public function emailExiste($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM clientes WHERE email = :email";
        if ($excludeId) {
            $sql .= " AND id != :excludeId";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":email", $email);
        if ($excludeId) {
            $stmt->bindParam(":excludeId", $excludeId);
        }
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function atualizarSenha($clienteId, $novaSenhaHash) {
        $sql = "UPDATE clientes SET senha = :senha WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":senha", $novaSenhaHash);
        $stmt->bindParam(":id", $clienteId);
        return $stmt->execute();
    }
}
?>