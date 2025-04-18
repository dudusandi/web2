<?php
class Cliente {
    private $id;
    private $nome;
    private $telefone;
    private $email;
    private $cartaoCredito;
    private $endereco;


    public function __construct($nome, $telefone, $email, $cartaoCredito, Endereco $endereco) {
        $this->nome = $nome;
        $this->telefone = $telefone;
        $this->email = $email;
        $this->cartaoCredito = $cartaoCredito;
        $this->endereco = $endereco;

    }

    // Getters
    public function getId() { return $this->id; }
    public function getNome() { return $this->nome; }
    public function getTelefone() { return $this->telefone; }
    public function getEmail() { return $this->email; }
    public function getCartaoCredito() { return $this->cartaoCredito; }
    public function getEndereco() { return $this->endereco; }


    // Setters
    public function setId($id) { $this->id = $id; }

}