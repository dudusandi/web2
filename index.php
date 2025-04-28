<?php
// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

// Verificação e redirecionamento
if (isLoggedIn()) {
    // Se já estiver logado, redireciona para a página principal
    header("Location: view/dashboard.php");
} else {
    // Se não estiver logado, redireciona para o login
    header("Location: view/login.php");
}
exit();
?>