<?php
session_start();

require_once '../dao/ClienteDao.php';
require_once '../config/database.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    

    if (empty($email) || empty($senha)) {
        header("Location: ../view/login.php?erro=1");
        exit;
    }
    
    try {
        $usuarioDao = new UsuarioDAO($pdo);
        
        $usuario = $usuarioDao->buscarPorEmailSenha($email, $senha);
        
        if ($usuario) {
            $_SESSION['usuario_id'] = $usuario->id;
            $_SESSION['usuario_nome'] = $usuario->nome;
            $_SESSION['usuario_email'] = $usuario->email;
            $_SESSION['logado'] = true;
            

            header("Location: ../view/dashboard.php");
            exit;
        } else {
            header("Location: ../view/login.php?erro=1");
            exit;
        }
    } catch (Exception $e) {
        header("Location: ../view/login.php?erro=2");
        exit;
    }
} else {
    header("Location: ../view/login.php");
    exit;
}
?>