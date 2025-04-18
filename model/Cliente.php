<?php
class Cliente {
    public $id;
    public $nome;
    public $email;
    public $telefone;
    public $cartaoCredito;

    public function __construct($nome = "", $email = "", $telefone = "", $cartaoCredito = "") {
        $this->nome = $nome;
        $this->email = $email;
        $this->telefone = $telefone;
        $this->cartaoCredito = $cartaoCredito;
    }
}
?>
