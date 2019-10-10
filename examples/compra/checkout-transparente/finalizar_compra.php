<?php
//=============================================//
//           Criando uma assinatura		       //
//=============================================//
require dirname(__FILE__)."/../../_autoload.class.php";
use CWG\PagSeguro\PagSeguroCompras;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroCompras($email, $token, $sandbox);

//Adiciona os itens da compra (ID do ITEM, DESCRICAO, VALOR, QUANTIDADE)
$pagseguro->adicionarItem('ITEM0001', 'Item 1', 10.00, 2);
$pagseguro->adicionarItem('ITEM0002', 'Item 2', 15.50, 1);

//URL para onde será enviado as notificações de alteração da compra (OPCIONAL)
$pagseguro->setNotificationURL('http://carloswgama.com.br/pagseguro/not/notificando.php');

//Nome do comprador igual a como esta no CARTÂO
$pagseguro->setNomeCliente("CARLOS W GAMA");
//Email do comprovador
$pagseguro->setEmailCliente("c73062863531198591643@sandbox.pagseguro.com.br");
//Informa o telefone DD e número
$pagseguro->setTelefone('11', '999999999');
//Informa o CPF
$pagseguro->setCPF('11111111111');
//$pagseguro->setCNPJ('74345378000163'); //Caso seja CNPJ
//$pagseguro->setCPF('74345378000163'); //Caso informe CNPJ no CPF, será chamado o método setCNPJ automaticamente

//Infora o Hash  gerado na página anterior (compra_completa.php), é obrigatório para comunicação com checkoutr transparente
$pagseguro->setHashCliente($_POST['hash']);
//Código usado pelo vendedor para identificar qual é a compra
$pagseguro->setReferencia("CWG004");

$pagseguro->setMetodoPagamento($_POST['metodo']);

//Informa o endereço RUA, NUMERO, COMPLEMENTO, BAIRRO, CIDADE, ESTADO, CEP
$pagseguro->setEnderecoCliente('Rua C', '99', 'COMPLEMENTO', 'BAIRRO', 'São Paulo', 'SP', '57000000');
    

//Dados necessários para pagamento com Cartão
if ($_POST['metodo'] == 'creditCard') {
    //Informa o ano de nascimento
    $pagseguro->setNascimentoCliente('01/01/1990');
    //Informa o Token do Cartão de Crédito gerado na etapa anterior (assinando.php)
    $pagseguro->setTokenCartao($_POST['token']);
    //Escolhe as parcelas  sendo (Número de Parcelas Escolhida, Valor das parcelas, Quantas parcelas sem juros)
    $pagseguro->setParcelas($_POST['parcelas'], $_POST['parcelas_valor'], 3);

} else if ($_POST['metodo'] == 'eft') { //Dados necessários para pagamento por Debito    
    //Informa o Banco para realizar o pagamento
    $pagseguro->setBancoDebito($_POST['banco']);
} 

//Se o pagamento for Cartão Retorna o Status do Pagamento (3 = Pago)
//Se for Debito ou boleto retorna a URL para realizar o pagamento
echo $pagseguro->pagarCheckoutTransparente();
