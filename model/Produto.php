<?php
class Produto {
    private $id;
    private $nome;
    private $descricao;
    private $foto;
    private $fornecedor_id;
    private $estoque_id;
    private $usuario_id;

    public function __construct($nome, $descricao, $foto, $fornecedor_id, $usuario_id) {
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->foto = $foto;
        $this->fornecedor_id = (int)$fornecedor_id;
        $this->usuario_id = (int)$usuario_id;
    }

    public function setId($id) {
        $this->id = (int)$id;
    }

    public function getId() {
        return $this->id;
    }

    public function setEstoqueId($estoque_id) {
        $this->estoque_id = (int)$estoque_id;
    }

    public function getEstoqueId() {
        return $this->estoque_id;
    }

    public function setFornecedorId($fornecedor_id) {
        $this->fornecedor_id = (int)$fornecedor_id;
    }

    public function getFornecedorId() {
        return $this->fornecedor_id;
    }

    public function getNome() {
        return $this->nome;
    }

    public function getDescricao() {
        return $this->descricao;
    }

    public function getFoto() {
        return $this->foto;
    }

    public function setUsuarioId($usuario_id) {
        $this->usuario_id = (int)$usuario_id;
    }

    public function getUsuarioId() {
        return $this->usuario_id;
    }
}
?>