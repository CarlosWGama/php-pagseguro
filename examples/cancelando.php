<?php
//=============================================//
//           Cancelando assinatura		       //
//=============================================//
require dirname(__FILE__)."/_autoload.class.php";
use CWG\PagSeguro\Pagseguro;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguro($email, $token, $sandbox);

$code = '5B87B45F7676F61CC42CFFA0175BF7AE';

try {
    print_r($pagseguro->suspenderAssinatura($code));
} catch (Exception $e) {
    echo $e->getMessage();
}