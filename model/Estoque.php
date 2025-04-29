<?php
class Estoque {
    private $quantidade;
    private $produto; 

    public function __construct(Produto $produto, $quantidade = 0) {
        $this->produto = $produto;
        $this->quantidade = (int)$quantidade;
    }

    public function adicionar($quantidade) {
        $this->quantidade += (int)$quantidade;
    }

    public function remover($quantidade) {
        $this->quantidade -= (int)$quantidade;
        if ($this->quantidade < 0) {
            $this->quantidade = 0; 
        }
    }

    public function getQuantidade() {
        return $this->quantidade;
    }

    public function getProduto() {
        return $this->produto;
    }
}
?>
