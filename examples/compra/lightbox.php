<?php
//=============================================//
//   Criando uma compra Usando Lightbox	       //
//=============================================//
require dirname(__FILE__)."/../_autoload.class.php";
use CWG\PagSeguro\PagSeguroCompras;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroCompras($email, $token, $sandbox);

//Nome do comprador (OPCIONAL)
$pagseguro->setNomeCliente("CARLOS W GAMA");	
//Email do comprovador (OPCIONAL)
$pagseguro->setEmailCliente("c73062863531198591643@sandbox.pagseguro.com.br");
//Código usado pelo vendedor para identificar qual é a compra (OPCIONAL)
$pagseguro->setReferencia("CWG004");	
//Adiciona os itens da compra (ID do ITEM, DESCRICAO, VALOR, QUANTIDADE)
$pagseguro->adicionarItem('ITEM0001', 'Item 1', 10.00, 2);
$pagseguro->adicionarItem('ITEM0002', 'Item 2', 15.50, 1);

//JavaScript caso a compra seja realizada (OPCIONAL)
$success = "window.location.href='obrigado.php'";

//JavaScript caso o lightbox seja fechado sem concluir a compra (OPCIONAL)
$abort = "window.location.href='index.php'";

try{
    $jsLightbox = $pagseguro->gerarLightbox($success, $abort);
    echo $jsLightbox;
} catch (Exception $e) {
    echo $e->getMessage();
}
