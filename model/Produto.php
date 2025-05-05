<?php
class Produto {
    private $id;
    private $nome;
    private $descricao;
    private $foto;
    private $fornecedor_id;
    private $estoque_id;
    private $usuario_id;
    private $quantidade;
    private $preco;
    public $fornecedor_nome; // Adiciona explicitamente

    public function __construct($nome, $descricao, $foto, $fornecedor_id, $usuario_id) {
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->foto = $foto;
        $this->fornecedor_id = $fornecedor_id;
        $this->usuario_id = $usuario_id;
    }

    // Getters e setters existentes
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }
    public function getNome() { return $this->nome; }
    public function getDescricao() { return $this->descricao; }
    public function getFoto() { return $this->foto; }
    public function getFornecedorId() { return $this->fornecedor_id; }
    public function getEstoqueId() { return $this->estoque_id; }
    public function setEstoqueId($estoque_id) { $this->estoque_id = $estoque_id; }
    public function getUsuarioId() { return $this->usuario_id; }
    public function getQuantidade() { return $this->quantidade; }
    public function setQuantidade($quantidade) { $this->quantidade = $quantidade; }
    public function getPreco() { return $this->preco; }
    public function setPreco($preco) { $this->preco = $preco; }

    // Getter para fornecedor_nome (opcional, já que é público)
    public function getFornecedorNome() {
        return $this->fornecedor_nome ?? 'Sem fornecedor';
    }
}
?>