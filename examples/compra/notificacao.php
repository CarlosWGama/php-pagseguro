<?php
//=============================================//
//           Consultando uma notificação      //
//=============================================//
require dirname(__FILE__)."/../_autoload.class.php";
use CWG\PagSeguro\PagSeguroCompras;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroCompras($email, $token, $sandbox);

//$_POST['notificationType'] = 'transaction';
//$_POST['notificationCode'] = 'EAE8FB-5A36423642F4-0224220F8642-FB6C4D';

//Caso seja uma notificação de compra (transaction)
if ($_POST['notificationType'] == 'transaction') {
    $codigo = $_POST['notificationCode']; //Recebe o código da notificação e busca as informações de como está a assinatura
    $response = $pagseguro->consultarNotificacao($codigo);
    print_r($response);die;
}