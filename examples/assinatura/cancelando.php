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

$codePagSeguro = '324CE6D30505CFB3344E1F8C5CFF9926';

try {
    print_r($pagseguro->cancelarAssinatura($codePagSeguro));
} catch (Exception $e) {
    echo $e->getMessage();
}