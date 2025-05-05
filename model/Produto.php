<?php
class Produto {
    private $id;
    private $nome;
    private $descricao;
    private $foto;
    private $fornecedorId;
    private $estoqueId;
    private $usuarioId;
    private $quantidade; 
    private $preco;      

    public function __construct($nome, $fornecedorId, $usuarioId, $quantidade = '', $preco = '', $descricao = '', $foto = '') {
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->foto = $foto;
        $this->fornecedorId = $fornecedorId;
        $this->usuarioId = $usuarioId;
        $this->quantidade = $quantidade;
        $this->preco = $preco;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getNome() { return $this->nome; }
    public function getDescricao() { return $this->descricao; }
    public function getFoto() { return $this->foto; }
    public function getFornecedorId() { return $this->fornecedorId; }
    public function getEstoqueId() { return $this->estoqueId; }
    public function getUsuarioId() { return $this->usuarioId; }
    public function getQuantidade() { return $this->quantidade; } // Novo getter
    public function getPreco() { return $this->preco; }           // Novo getter

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setNome($nome) { $this->nome = $nome; }
    public function setDescricao($descricao) { $this->descricao = $descricao; }
    public function setFoto($foto) { $this->foto = $foto; }
    public function setFornecedorId($fornecedorId) { $this->fornecedorId = $fornecedorId; }
    public function setEstoqueId($estoqueId) { $this->estoqueId = $estoqueId; }
    public function setUsuarioId($usuarioId) { $this->usuarioId = $usuarioId; }
    public function setQuantidade($quantidade) { $this->quantidade = $quantidade; } // Novo setter
    public function setPreco($preco) { $this->preco = $preco; }                     // Novo setter
}
?>