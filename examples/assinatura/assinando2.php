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

//Nome do comprador igual a como esta no CARTÂO
$pagseguro->setNomeCliente("CARLOS W GAMA");	
//Email do comprovador
$pagseguro->setEmailCliente("c73062863531198591643@sandbox.pagseguro.com.br");
//Informa o telefone DD e número
$pagseguro->setTelefone('11', '999999999');
//Informa o CPF
$pagseguro->setCPF('11111111111');
//Informa o endereço RUA, NUMERO, COMPLEMENTO, BAIRRO, CIDADE, ESTADO, CEP
$pagseguro->setEnderecoCliente('Rua C', '99', 'COMPLEMENTO', 'BAIRRO', 'São Paulo', 'SP', '57000000');
//Informa o ano de nascimento
$pagseguro->setNascimentoCliente('01/01/1990');
//Infora o Hash  gerado na etapa anterior (assinando.php), é obrigatório para comunicação com checkoutr transparente
$pagseguro->setHashCliente($_POST['hash']);
//Informa o Token do Cartão de Crédito gerado na etapa anterior (assinando.php)
$pagseguro->setTokenCartao($_POST['token']);
//Código usado pelo vendedor para identificar qual é a compra
$pagseguro->setReferencia("CWG004");	
//Plano usado (Esse código é criado durante a criação do plano)
$pagseguro->setPlanoCode('E488FBA13434E41114179FB619875F62');

try{
    $codigo = $pagseguro->assinaPlano();
    echo 'O código unico da assinatura é: ' . $codigo;
} catch (Exception $e) {
    echo $e->getMessage();
}
