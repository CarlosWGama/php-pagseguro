<?php
//=============================================//
//           Cancelando assinatura		       //
//=============================================//
require dirname(__FILE__)."/../_autoload.class.php";
use CWG\PagSeguro\PagSeguroAssinaturas;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroAssinaturas($email, $token, $sandbox);

$codePagSeguro = '1BA8C57CD4D4F3F114A8FFB47768EA2F';

try {
    print_r($pagseguro->setHabilitarAssinatura($codePagSeguro, true));
} catch (Exception $e) {
    echo $e->getMessage();
}