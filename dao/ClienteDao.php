<?php
require_once '../config/database.php';
require_once '../model/Cliente.php';

class UsuarioDAO {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function buscarPorEmailSenha($email, $senha) {
        $sql = "SELECT * FROM usuarios WHERE email = :email AND senha = :senha";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":senha", $senha);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);
            $usuario = new Usuario($linha['nome'], $linha['email'], "");
            $usuario->id = $linha['id'];
            return $usuario;
        }

        return null;
    }


    public function cadastrarCliente($nome, $email, $senha) {
        try {
            $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":nome", $nome);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":senha", $senha);
            return $stmt->execute(); 
        } catch (PDOException $e) {
            if ($e->getCode() == '23505') { 
                throw new Exception("Este email já está cadastrado no sistema.");
            }
            throw new Exception("Erro ao cadastrar: " . $e->getMessage());
        }
    }
}
?>