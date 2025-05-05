<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Cliente</title>
    <link rel="stylesheet" href="cadastro.css">
</head>
<body>
    <h2>Cadastro de Cliente</h2>
    
    <?php if (isset($_GET['sucesso'])): ?>
        <div class="success-message">
            Cadastro realizado com sucesso! <a href="../view/login.php">Clique aqui para fazer login</a>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['erro'])): ?>
        <div class="error-message">
            <?php
                $erro = $_GET['erro'];
                switch($erro) {
                    case 'campos_obrigatorios':
                        echo "Todos os campos obrigatórios devem ser preenchidos.";
                        break;
                    case 'email_invalido':
                        echo "O email informado não é válido.";
                        break;
                    case 'email_existente':
                        echo "Este email já está cadastrado em nosso sistema.";
                        break;
                    default:
                        echo "Ocorreu um erro ao processar seu cadastro. Tente novamente.";
                }
            ?>
        </div>
    <?php endif; ?>

    <form action="../controllers/cadastrar_cliente.php" method="POST" onsubmit="return validarFormulario()">
        <div class="form-group">
            <label for="nome">Nome Completo:*</label>
            <input type="text" id="nome" name="nome" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email:*</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="telefone">Telefone:</label>
                <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="senha">Senha:*</label>
                <input type="password" id="senha" name="senha" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha:*</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                <div id="senha-error" class="error-message" style="display: none;">As senhas não coincidem.</div>
            </div>
        </div>


        <h3>Endereço</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="rua">Rua:*</label>
                <input type="text" id="rua" name="rua" required>
            </div>
            
            <div class="form-group" style="max-width: 100px;">
                <label for="numero">Número:*</label>
                <input type="text" id="numero" name="numero" required>
            </div>
        </div>

        <div class="form-group">
            <label for="complemento">Complemento:</label>
            <input type="text" id="complemento" name="complemento">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="bairro">Bairro:*</label>
                <input type="text" id="bairro" name="bairro" required>
            </div>
            
            <div class="form-group">
                <label for="cep">CEP:*</label>
                <input type="text" id="cep" name="cep" required placeholder="00000-000">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="cidade">Cidade:*</label>
                <input type="text" id="cidade" name="cidade" required>
            </div>
            
            <div class="form-group">
                <label for="estado">Estado:*</label>
                <select id="estado" name="estado" required>
                    <option value="">Selecione</option>
                    <option value="AC">Acre</option>
                    <option value="AL">Alagoas</option>
                    <option value="AP">Amapá</option>
                    <option value="AM">Amazonas</option>
                    <option value="BA">Bahia</option>
                    <option value="CE">Ceará</option>
                    <option value="DF">Distrito Federal</option>
                    <option value="ES">Espírito Santo</option>
                    <option value="GO">Goiás</option>
                    <option value="MA">Maranhão</option>
                    <option value="MT">Mato Grosso</option>
                    <option value="MS">Mato Grosso do Sul</option>
                    <option value="MG">Minas Gerais</option>
                    <option value="PA">Pará</option>
                    <option value="PB">Paraíba</option>
                    <option value="PR">Paraná</option>
                    <option value="PE">Pernambuco</option>
                    <option value="PI">Piauí</option>
                    <option value="RJ">Rio de Janeiro</option>
                    <option value="RN">Rio Grande do Norte</option>
                    <option value="RS">Rio Grande do Sul</option>
                    <option value="RO">Rondônia</option>
                    <option value="RR">Roraima</option>
                    <option value="SC">Santa Catarina</option>
                    <option value="SP">São Paulo</option>
                    <option value="SE">Sergipe</option>
                    <option value="TO">Tocantins</option>
                </select>
            </div>
        </div>

        <button type="submit">Cadastrar</button>
        
        <p style="text-align: center; margin-top: 20px;">
            Já tem uma conta? <a href="../view/login.php">Faça login aqui</a>
        </p>
    </form>

    <script>
        // Validação de senha
        function validarFormulario() {
            const senha = document.getElementById('senha').value;
            const confirmarSenha = document.getElementById('confirmar_senha').value;
            const senhaError = document.getElementById('senha-error');
            
            if (senha !== confirmarSenha) {
                senhaError.style.display = 'block';
                return false;
            } else {
                senhaError.style.display = 'none';
                return true;
            }
        }

        // Máscaras para campos
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            
            if (value.length > 0) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                if (value.length > 10) {
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                }
            }
            
            e.target.value = value;
        });

        document.getElementById('cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) value = value.substring(0, 8);
            
            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            }
            
            e.target.value = value;
        });

    </script>
</body>
</html>