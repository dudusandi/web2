<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if (isset($_GET['erro'])): ?>
        <p style="color: red;">Email ou senha inválidos.</p>
    <?php endif; ?>
    <form action="../controllers/LoginController.php" method="POST">
        <label>Email:</label><br>
        <input type="email" name="email" required><br>
        <label>Senha:</label><br>
        <input type="password" name="senha" required><br><br>
        <button type="submit">Entrar</button>
    </form>
    <a href="cadastro.php">Cadastre-se</a>
</body>
</html>
