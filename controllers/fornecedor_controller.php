<?php
require_once '../model/fornecedor.php';
require_once '../dao/fornecedor_dao.php'; 
class FornecedorController {
    private $fornecedorDAO;

    public function __construct() {
        $this->fornecedorDAO = new FornecedorDAO(); 
    }

    // Cadastra um novo fornecedor
    public function cadastrarFornecedor($nome, $descricao, $telefone, $email, $rua, $numero, $complemento, $bairro, $cep, $cidade, $estado) {
        $endereco = new Endereco($rua, $numero, $bairro, $cep, $cidade, $estado, $complemento);
        $fornecedor = new Fornecedor($nome, $descricao, $telefone, $email, $endereco);
        $this->fornecedorDAO->cadastrarFornecedor($fornecedor);
        header('Location: ../public/view/cadastro_fornecedor.php?mensagem=Cadastro realizado com sucesso');
    }

    // Lista todos os fornecedores
    public function listarFornecedores() {
        $fornecedores = $this->fornecedorDAO->listarTodos(); // Método hipotético no DAO
        return $fornecedores;
    }

    // Edita um fornecedor existente
    public function editarFornecedor($id, $nome, $descricao, $telefone, $email, $endereco) {
        $fornecedor = $this->fornecedorDAO->buscarPorId($id); // Método hipotético no DAO
        if ($fornecedor) {
            $fornecedor->setNome($nome);
            $fornecedor->setDescricao($descricao);
            $fornecedor->setTelefone($telefone);
            $fornecedor->setEmail($email);
            $fornecedor->setEndereco($endereco);
            $this->fornecedorDAO->atualizar($fornecedor); // Método hipotético no DAO
            header('Location: ../public/view/listar_fornecedores.php');
        }
    }
}

// Exemplo de uso (pode ser chamado via requisição ou rota)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new FornecedorController();
    if (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
        $controller->cadastrarFornecedor(
            $_POST['nome'],
            $_POST['descricao'],
            $_POST['telefone'],
            $_POST['email'],
            $_POST['rua'],
            $_POST['numero'],
            $_POST['complemento'],
            $_POST['bairro'],
            $_POST['cep'],
            $_POST['cidade'],
            $_POST['estado']
        );
    }elseif (isset($_POST['acao']) && $_POST['acao'] === 'editar') {
        $controller->editarFornecedor(
            $_POST['id'],
            $_POST['nome'],
            $_POST['descricao'],
            $_POST['telefone'],
            $_POST['email'],
            $_POST['endereco']
        );
    }
}
?>