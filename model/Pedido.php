<?php
class Pedido {
    private $numero;
    private $dataPedido;
    private $dataEntrega;
    private $situacao; 
    private $cliente;
    private $itensPedido = array();
    private $valor_total;
    
    public function __construct($numero, $dataPedido, $cliente, $valor_total = 0.0, $dataEntrega = null, $situacao = "NOVO") {
        $this->numero = $numero;
        $this->dataPedido = $dataPedido;
        $this->cliente = $cliente;
        $this->valor_total = (float)$valor_total;
        $this->dataEntrega = $dataEntrega;
        $this->situacao = $situacao;
    }
    
    public function getNumero() {
        return $this->numero;
    }
    
    public function getDataPedido() {
        return $this->dataPedido;
    }
    
    public function getDataEntrega() {
        return $this->dataEntrega;
    }
    
    public function getSituacao() {
        return $this->situacao;
    }
    
    public function getCliente() {
        return $this->cliente;
    }
    
    public function &getItensPedido() {
        return $this->itensPedido;
    }
    
    public function getValorTotal() {
        return $this->valor_total;
    }
    
    public function setNumero($numero) {
        $this->numero = $numero;
    }
    
    public function setDataPedido($dataPedido) {
        $this->dataPedido = $dataPedido;
    }
    
    public function setDataEntrega($dataEntrega) {
        $this->dataEntrega = $dataEntrega;
    }
    
  
}

?>