<?php
//=============================================//
//           Criando uma assinatura		       //
//=============================================//
require dirname(__FILE__)."/_autoload.class.php";
use CWG\PagSeguro\Pagseguro;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguro($email, $token, $sandbox);

//Nome do comprador
$pagseguro->setNomeCliente("Carlos W. Gama");	
//Email do comprovador
$pagseguro->setEmailCliente("c73062863531198591643@sandbox.pagseguro.com.br");
//Código usado pelo vendedor para identificar qual é a compra
$pagseguro->setReferencia("CWG003");	
//Descrição de quem está cobrando
$pagseguro->setRazao("-CWG-");		
//Valor cobrado
$pagseguro->setValor('10.00');			
//Local para o comprador será redicionado após a compra com o código (code) identificador da assinatura
$pagseguro->setRedirectURL('http://carloswgama.com.br/pagseguro/consulta.php');	
//OPCIONAL (URL para onde as notificações serão enviadas sempre que houver atualizar no status da compra)
$pagseguro->setNotificationURL('http://carloswgama.com.br/pagseguro/consulta.php');	
//A pessoa é cobrada em quantos meses? 
$pagseguro->setPeriodicidade(1);		
//0 (WEEKLY - Semanalmente)	| 1 (MONTHLY - Mensalmente) | 2 (BIMONTHLY - Bimestralmente) | 3 (TRIMONTHLY - Trimestralmente) | 6 (SEMIANNUALLY - Semestralmente) | 12 (YEARLY - Anualmente)

try{
    $url = $pagseguro->gerarSolicitacaoPagSeguro();
    print_r($url);	//URL para realizar pagamento
} catch (Exception $e) {
    echo $e->getMessage();
}