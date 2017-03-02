<?php
//=============================================//
//           Consultando assinatura		       //
//=============================================//
require dirname(__FILE__)."/_autoload.class.php";
use CWG\PagSeguro\Pagseguro;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguro($email, $token, $sandbox);

try {
    $code = $_GET['code']; 

    //Consulta por CÓDIGO do PagSeguro
    print_r($pagseguro->consultarAssinatura($code));

    //Consulta por intervalo
    print_r($pagseguro->consultarAssinaturaPeriodo('2016-06-13 00:00', '2016-06-14 17:30'));

    //Buscar código pelo valor passado na referencia
    echo $code = $pagseguro->getPreApprovalCodeByVenda("CWG002", '2016-06-14 00:00', '2016-06-14 17:30');
} catch (Exception $e) {
    echo $e->getMessage();
}