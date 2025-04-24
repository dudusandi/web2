<?php
class Produto {
    private $id;
    private $nome;
    private $descricao;
    private $foto;
    private $fornecedor;
    private $estoque;

    public function __construct($nome, $descricao, $foto, $fornecedor) {
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->foto = $foto;
        $this->fornecedor = $fornecedor;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    public function setEstoque($estoque) {
        $this->estoque = (int)$estoque;
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

    public function getFornecedor() {
        return $this->fornecedor;
    }

    public function getEstoque() {
        return $this->estoque;
    }
}