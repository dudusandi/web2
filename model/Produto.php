<?php
class Produto {
    private $id;
    private $nome;
    private $descricao;
    private $foto;
    private $fornecedor;
    private $usuario_id;

    public function __construct($nome, $descricao, $foto, $fornecedor, $usuario_id) {
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->foto = $foto;
        $this->fornecedor = $fornecedor;
        $this->usuario_id = (int)$usuario_id;
    }

    public function setId($id) {
        $this->id = (int)$id;
    }

    public function getId() {
        return $this->id;
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

    public function setUsuarioId($usuario_id) {
        $this->usuario_id = (int)$usuario_id;
    }

    public function getUsuarioId() {
        return $this->usuario_id;
    }
}
?>
