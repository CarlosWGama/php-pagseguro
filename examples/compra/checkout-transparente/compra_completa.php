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

//Informar os produtos NO CASO DE CARTÃO DE CREDITO para calcular as parcelas
$pagseguro->adicionarItem('ITEM0001', 'Item 1', 10.00, 2);
$pagseguro->adicionarItem('ITEM0002', 'Item 2', 15.50, 1);
$pagseguro->setParcelasSemJuros(3); //Informa quantidade de parcelas sem juros

//URL para onde os dados da compra será realizado para confirmar com o PagSeguro
$urlFinalizar = 'http://localhost/pagseguro/examples/compra/checkout-transparente/finalizar_compra.php';

//JavaScript opcional para realizar ao receber os dados de retorno da URL final
$jsOpcional = 'alert("Alerta do $jsOpcional"); console.log(response); if (response.success) alert("Concluido com sucesso");';

//Informa URL para completar a compra, JS (opcional) a ser feito e TRUE (OPCIONAL) caso queira importar JQuery
$js = $pagseguro->preparaCheckoutTransparente($urlFinalizar, $jsOpcional, true);
?>
<!--=========================== CARTÃO DE CREDITO ===========================-->
<h1>Cartão de Crédito</h1>
<h2> Campos Obrigatórios </h2>
<p>Número do Cartão</p>
<!-- (OPCIONAL) A FUNÇÃO "PagSeguroAtualizaParcela()" pode ser chamada sempre que quiser atualizar o parcelamento dependendo do cartão -->
<input type="text" id="pagseguro_cartao_numero" value="4111111111111111" onblur="PagSeguroAtualizaParcela()"/>

<p>CVV do cartão</p>
<input type="text" id="pagseguro_cartao_cvv" value="123"/>

<p>Mês de expiração do Cartao</p>
<input type="text" id="pagseguro_cartao_mes" value="12"/>

<p>Ano de Expiração do Cartão</p>
<input type="text" id="pagseguro_cartao_ano" value="2030"/>

<p>Parcelas</p>
<select id="pagseguro_cartao_parcela">
    <option></option>
</select>

<br/>

<!-- Use A classe pagseguro-pagar-cartao para completar via Cartão -->
<button id="botao_comprar" class="pagseguro-pagar-cartao">Comprar Cartão</button>

<!-- =========================== DEBITO =========================== -->
<h1>Debito</h1>

<p>Selecionar Banco</p>
<select onchange="selecionaBanco($(this).val())">
    <option value="bancodobrasil">Banco do Brasil</option>
    <option value="banrisul">Barinsul</option>
    <option value="bradesco">Bradesco</option>
    <option value="itau">Itau</option>
</select>

<!-- Use A classe pagseguro-pagar-debito para completar via Debito -->
<button id="botao_comprar" class="pagseguro-pagar-debito">Comprar Debito</button>

<!--=========================== BOLETO ===========================-->
<h1>Boleto</h1>
<!-- Use A classe pagseguro-pagar-boleto para completar via Boleto -->
<button id="botao_comprar" class="pagseguro-pagar-boleto">Comprar Boleto</button>


<?=$js['completo']; //Importa o Código do CheckoutTransparente?>