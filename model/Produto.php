<?php
class Produto {
    private $id;
    private $nome;
    private $descricao;
    private $foto;
    private $fornecedor;
    private $estoque;
    private $usuario_id;

    public function __construct($nome, $descricao, $foto, $fornecedor, $usuario_id) {
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->foto = $foto;
        $this->fornecedor = $fornecedor;
        $this->usuario_id = (int)$usuario_id; // Garante que usuario_id seja inteiro
    }

    public function setId($id) {
        $this->id = (int)$id;
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

    public function setUsuarioId($usuario_id) {
        $this->usuario_id = (int)$usuario_id;
    }

    public function getUsuarioId() {
        return $this->usuario_id;
    }
}