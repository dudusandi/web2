<?php
require_once '../dao/usuarioDao.php';
require_once '../config/database.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);


    if (empty($nome) || empty($email) || empty($senha)) {
        die("Todos os campos são obrigatórios.");
    }

    try {
        $usuarioDao = new UsuarioDAO($pdo);

        $resultado = $usuarioDao->cadastrarUsuario($nome, $email, $senha);

        if ($resultado) {
            echo "Cadastro realizado com sucesso! <a href='../view/login.php'>Clique aqui para fazer login</a>";
        } else {
            echo "Erro ao cadastrar usuário. Tente novamente.";
        }
    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage();
    }
}
?>