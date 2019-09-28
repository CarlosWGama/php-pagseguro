<?php
//=============================================//
//    Cancelando a Compra                      //
//=============================================//
require dirname(__FILE__)."/../_autoload.class.php";
use CWG\PagSeguro\PagSeguroCompras;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroCompras($email, $token, $sandbox);

$codigoTransacao = 'D76FB9C45A7848888094BBA4C3718BC9';
try {
    //===== Cancelando uma compra =====//
    //Só pode ser usado em compras não aprovadas ainda
    //Ou seja, nos estados: Aguardando pagamento (1) ou Em análise (2) 
    $pagseguro->cancelar($codigoTransacao);
} catch (Exception $e) {
    echo "Selecione um código de pagamento válido";
    echo $e->getMessage();
}