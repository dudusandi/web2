<?php
$mensagem = isset($_GET['mensagem']) ? $_GET['mensagem'] : '';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Fornecedor</title>
    <link rel="stylesheet" href="cadastro.css">
</head>
<body>
    <h2>Cadastro de Fornecedor</h2>

    <?php if ($mensagem): ?>
        <div class="mensagem <?php echo strpos($mensagem, 'sucesso') !== false ? 'sucesso' : 'erro'; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <form action="../controllers/fornecedor_controller.php" method="POST">
        <input type="hidden" name="acao" value="cadastrar">

        <div class="form-group">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>
        </div>

        <div class="form-group">
            <label for="descricao">Descrição:</label>
            <textarea id="descricao" name="descricao" rows="4" required></textarea>
        </div>

        <div class="form-group">
            <label for="telefone">Telefone:</label>
            <input type="text" id="telefone" name="telefone" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <h3>Endereço</h3>
        <div class="form-group">
            <label for="rua">Rua:</label>
            <input type="text" id="rua" name="rua" required>
        </div>
        <div class="form-group">
            <label for="numero">Número:</label>
            <input type="text" id="numero" name="numero" required>
        </div>
        <div class="form-group">
            <label for="complemento">Complemento:</label>
            <input type="text" id="complemento" name="complemento">
        </div>
        <div class="form-group">
            <label for="bairro">Bairro:</label>
            <input type="text" id="bairro" name="bairro" required>
        </div>
        <div class="form-group">
            <label for="cep">CEP:</label>
            <input type="text" id="cep" name="cep" required>
        </div>
        <div class="form-group">
            <label for="cidade">Cidade:</label>
            <input type="text" id="cidade" name="cidade" required>
        </div>
        <div class="form-group">
            <label for="estado">Estado:</label>
            <input type="text" id="estado" name="estado" required>
        </div>

        <button type="submit">Cadastrar Fornecedor</button>
        <a href="../view/dashboard.php" class="btn btn-secondary">Voltar</a>

    </form>
</body>
</html>