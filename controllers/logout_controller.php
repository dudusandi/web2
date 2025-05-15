<?php
session_start();

// Preservar os carrinhos existentes
$carrinhos = isset($_SESSION['carrinhos']) ? $_SESSION['carrinhos'] : [];

// Limpar apenas os dados do usuário atual
unset($_SESSION['usuario_id']);
unset($_SESSION['usuario_email']);
unset($_SESSION['usuario_nome']);
unset($_SESSION['is_admin']);

// Manter os carrinhos para que eles persistam entre sessões
$_SESSION['carrinhos'] = $carrinhos;

header("Location: ../view/login.php");
exit;
?>