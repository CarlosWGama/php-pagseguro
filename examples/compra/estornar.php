<?php
//=============================================//
//              Estornando Compra              //
//=============================================//
require dirname(__FILE__)."/../_autoload.class.php";
use CWG\PagSeguro\PagSeguroCompras;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroCompras($email, $token, $sandbox);

$codigoTransacao = 'D76FB9C45A7848888094BBA4C3718BC9';
try {
    //Só pode ser usado em compras já aprovadas 
    //Ou seja, nos estados: Paga (3) Disponível (4) ou Em Disputa (5) 
    $pagseguro->estornar($codigoTransacao);

    //Opcionalmente pode informar a quantia a estornar. Senão informado, estorna todo valor
    //$pagseguro->estornar($codigoTransacao, 178.99);
} catch (Exception $e) {
    echo "Selecione um código de pagamento válido";
    echo $e->getMessage();
}