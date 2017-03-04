<?php
//=============================================//
//           Consultando Compra	    	       //
//=============================================//
require dirname(__FILE__)."/../_autoload.class.php";
use CWG\PagSeguro\PagSeguroCompras;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroCompras($email, $token, $sandbox);

echo "<h2>Consulta pelo código de Transação</h2>";
//Pelo cóigo da transação
$codigoTransacao = 'CF748673-0190-4B15-8ACA-A10192E7C1D4'; //È o código gerado no ato da compra pelo PagSeguro
$response = $pagseguro->consultarCompra($codigoTransacao);
print_r($response);

echo "<br/><hr/>";

echo "<h2>Consulta pelo código de Referencia</h2>";
//Pelo Código da Referencia
$referencia = 'CWG004'; //È o código gerado no seu site ao criar a solicitação de compra
$response = $pagseguro->consultarCompraByReferencia($referencia);
print_r($response);

