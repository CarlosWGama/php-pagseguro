<?php
//=============================================//
//           Criando uma assinatura		       //
//=============================================//
require dirname(__FILE__)."/../_autoload.class.php";
use CWG\PagSeguro\PagSeguroAssinaturas;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroAssinaturas($email, $token, $sandbox);

$codigoPlano = 'E488FBA13434E41114179FB619875F62';
$url = $pagseguro->assinarPlanoCheckout($codigoPlano);

echo 'URL para o Checkout: ' . $url;