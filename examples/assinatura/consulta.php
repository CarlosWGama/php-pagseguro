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

$codeAssinatura = '131F919E44449D62244DEFAA0FEB334C';
$response = $pagseguro->consultaAssinatura($codeAssinatura);
print_r($response);die;
