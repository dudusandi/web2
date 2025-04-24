<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ./login.php');
    exit;
}
?>

<h1>Bem-vindo, <?= $_SESSION['usuario_nome'] ?>!</h1>
<a href="../controllers/logout_controller.php">Sair</a>
<a href="../controllers/cadastrar_produto.php">Cadastrar Produto</a>
