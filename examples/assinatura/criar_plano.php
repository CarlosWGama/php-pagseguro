<?php
//=============================================//
//           Criando Plano	        	       //
//=============================================//
require dirname(__FILE__)."/../_autoload.class.php";
use CWG\PagSeguro\PagSeguroAssinaturas;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroAssinaturas($email, $token, $sandbox);

//Cria um nome para o plano
$pagseguro->setReferencia('Plano_CWG_01');

//Cria uma descrição para o plano
$pagseguro->setDescricao('Libera o acesso ao portal por 3 meses. A assinatura voltará a ser cobrada a cada 3 meses.');

//Valor a ser cobrado a cada renovação
$pagseguro->setValor(30.00);

//De quanto em quanto tempo será realizado uma nova cobrança (MENSAL, BIMESTRAL, TRIMESTRAL, SEMESTRAL, ANUAL)
$pagseguro->setPeriodicidade(PagSeguroAssinaturas::TRIMESTRAL);

//=== Campos Opcionais ===//
//Após quanto tempo a assinatura irá expirar após a contratação = valor inteiro + (DAYS||MONTHS||YEARS). Exemplo, após 5 anos
$pagseguro->setExpiracao(5, 'YEARS');

//URL para redicionar a pessoa do portal PagSeguro para uma página de cancelamento no portal
$pagseguro->setURLCancelamento('http://carloswgama.com.br/pagseguro/not/cancelando.php');

//Local para o comprador será redicionado após a compra com o código (code) identificador da assinatura
$pagseguro->setRedirectURL('http://carloswgama.com.br/pagseguro/not/assinando.php');		

//Máximo de pessoas que podem usar esse plano. Exemplo 10.000 pessoas podem usar esse plano
$pagseguro->setMaximoUsuariosNoPlano(10000);

//=== Cria o plano ===//
try {
    $codigoPlano = $pagseguro->criarPlano();
    echo "O Código do seu plano para realizar assinaturas é: " . $codigoPlano;
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}