<?php
//=============================================//
//           Cancelando assinatura		       //
//=============================================//
require ("pagseguro.class.php");

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguro($email, $token, $sandbox);

$code = '5B87B45F7676F61CC42CFFA0175BF7AE';

print_r($pagseguro->suspenderAssinatura($code));