# PHP - Pagseguro V3
Classe para realizar compras sem recorrencia no PagSeguro

*Para realizar compras recorrentes no PagSeguro como assinaturas ou compras com checkout no ambiente do PagSeguro, olhe os examples na página abaixo:*


[Pagamentos com Checkout Padrão](https://github.com/CarlosWGama/php-pagseguro/tree/master/examples/compra/)

[Pagamentos Recorrentes com PagSeguro](https://github.com/CarlosWGama/php-pagseguro/tree/master/examples/assinatura)

[Documentação do PagSeguro Pagamento Padrão - Compra sem usar Classe](https://dev.pagseguro.uol.com.br/reference)

-----
O código dessa seção serve para realizar compras com checkout transparente.


## Gerando Token

Para gerar o Token da sua conta do PagSeguro, logar na conta, ir em [Minha Conta >> Preferências >> Integração](https://pagseguro.uol.com.br/preferencias/integracoes.jhtml) e solicar para gerar um token

![Token em produção](http://carloswgama.com.br/pagseguro/pagseguro_gerar_token.jpg)

No Sandbox para testes, seu token pode ser acessado em [Perfil de Integração >> Vendedor](https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html)

![Token no Sandbox](http://carloswgama.com.br/pagseguro/pagseguro_gerar_token_sandbox.jpg)

## Baixando o projeto

Para usar esse projeto, basta baixar esse repositório em seu projeto e importar a classe em src/PagSeguroAssinaturas.php ou usar o composer que é o mais indicado:

```
composer require carloswgama/php-pagseguro
```

Caso seu projeto já possua um arquivo composer.json, você pode também adiciona-lo nas dependências require e rodar um composer install:
```
{
    "require": {
        "carloswgama/php-pagseguro": "3.*"
    }
}
```

## Criando uma compra com checkout transparente 

O Checkout transparente é aquele que ocorre no próprio ambiente do serviço e não no PagSeguro. 

Para realizá-lo é necessário antes buscar um hash que identifica o seu cliente.

Caso pague via cartão, precisa gerar o token do cartão do cliente (Mais detalhes podem ser visto na documentação do PagSeguro)
Caso pague via debito precisa escolher o banco.

No código abaixo podemos usar o método preparaCheckoutTransparente() para gerar todo javascript necessário para criar um ambiente de checkout transparente com Cartão, Debito e Boleto:

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
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
$jsOpcional = 'alert("Alerta do $jsOpcional"); if (response.success) alert("Concluido com sucesso");';

//Informa URL para completar a compra, JS (opcional) a ser feito e TRUE (OPCIONAL) caso queira importar JQuery
$js = $pagseguro->preparaCheckoutTransparente($urlFinalizar, $jsOpcional, true);
?>
<!--=========================== CARTÃO DE CREDITO ===========================-->
<h1>Cartão de Crédito</h1>
<h2> Campos Obrigatórios </h2>
<p>Número do Cartão</p>
<input type="text" id="pagseguro_cartao_numero" value="4111111111111111"/>

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
``` 

**OBS: Nâo Esqueça de importar o JavaScript gerado ($js['completo']) no final da página, é ele que terá todos os campos e comandos javascripts necessários*


####Nesse exemplo acima, nós precisamos dos seguintes campos para compras com cartão:

| Campo ID                | Descrição                                           | Exemplo          |
|-------------------------|-----------------------------------------------------|------------------|
| pagseguro_cartao_numero | Informa o número do cartão                          | 4111111111111111 |
| pagseguro_cartao_cvv    | Informa o código de segurança atrás do cartão (CVV) | 123              |
| pagseguro_cartao_mes    | Informa o mês que o cartão expira                   | 12               |
| pagseguro_cartao_ano    | Informa o ano que o cartão expira                   | 2030             |
| pagseguro_cartao_parcela| Informa o nunmero de parcelas (Crie um select vázio, o script cuidará de preenché-lo) |              |


####Para pagamentos via debito será preciso os seguintes campos:

use a função selecionaBanco('nome_banco') para informar qual banco será usado no pagamento. Os valores possíveis são:
- bancodobrasil
- banrisul
- bradesco
- itau

-------------
Além destes campos, o javascript irá criar 3 automaticamente novos campos:

| Campo ID                  |      Formato de Pagamento             | Descrição                                                                                                                       |
|---------------------------|--------------------------------|---------------------------------------------------------------------------------------------------------------------------------|
| pagseguro_cliente_hash    |         Todos               | Cria um Hash unico para identificar o cliente com a compra com checkout transparente. Será usado na segunda parte da compra |
| pagseguro_cartao_token    |          Cartão             | Cria um Token unico com todas informações necessárias do cartão do cliente. Será usado na segunda parte da compra.          |
| pagseguro_cartao_bandeira |          Cartão             | Identifica qual é a bandeira do cartão do cliente. É usado apenas para gerar o pagseguro_cartao_token, portanto deve ser gerado antes do token     
| pagseguro_debito_banco |          Debito             | Identifica qual é o banco será criado o link para pagamento, através da escolha do método selecionaBanco()  |


O campo pagseguro_cliente_hash e pagseguro_cartao_token são usados na solicitação de compra na parte seguinte. E esses dados são gerados através das funções javascripts:

| Campo ID                  | Função Responsável por Gerar |
|---------------------------|------------------------------|
| pagseguro_cliente_hash    | PagSeguroBuscaHashCliente(); |
| pagseguro_cartao_token    | PagSeguroBuscaToken();       |
| pagseguro_cartao_bandeira | PagSeguroBuscaBandeira();    |


Para enviar a compra para a próxima etapa, crie um botão com a seguinte class:
| Classe                    | Tipo Pagamento |
|---------------------------|------------------------------|
| pagseguro-pagar-cartao    | Realiza a compra via Cartão |
| pagseguro-pagar-debito    | Gera a compra e retorna uma link para pagamento via debito |
| pagseguro-pagar-boleto    | Gera a compra e retorna um link para o Boleto |


Os dados serão enviados para a próxima página, que será mostrado na seção seguinte e deve retornar um json (response) contendo os atributos
| Atributo  | Dado |
|-----------|-------------------------------------------------------------------------------|
| success   | TRUE ou FALSE                                                                 |
| method    | cartao, boleto, debito                                                        |
| url       | Uma URL para pagamento em caso de boleto ou debito                            |
| status    | O status da compra em caso de cartão (Olhar os status da compra no pagseguro) |
| message   | Uma mensagem em caso de erro                                                  |

Também caso deseje, pode adicionar alguma função extra como redirecioanr o usuario para outra página, adicionando os comandos javascripts extras ($jsOpcional) como segundo parametro do método prepararCheckoutTransparente

Após você pode implementar essa tela, as informações devem ser enviadas para uma outra página que irá concluir a compra, então informe a url no método preparaCheckoutTransparente para a página de concluir o pagamento:

```php
//Informa URL para a página que completa a compra, JS (opcional) a ser feito e TRUE (OPCIONAL) caso queira importar JQuery
$js = $pagseguro->preparaCheckoutTransparente($urlFinalizar, $jsOpcional, true);
```

### Concluindo a compra

Na nova página que será acessada via Ajax pelo JavaScript, informe os demais dados do cliente, url de notificação e os dados que foram enviados pelo JavaScript.

No final, peça para retornar o Json que será criado pelo método pagarCheckoutTransparente().

Abaixo segue um exemplo do uso:

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
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
//$pagseguro->setCNPJ('74345378000163'); //Ou CNPJ

//Infora o Hash  gerado na página anterior (compra_completa.php), é obrigatório para comunicação com checkoutr transparente
$pagseguro->setHashCliente($_POST['hash']);
//Código usado pelo vendedor para identificar qual é a compra
$pagseguro->setReferencia("CWG004");

//Método de pagamento (cartão, debito ou boleto), esse dado é enviado pelo JavaScript
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
``` 

----------------------
#### Outros Links:
[Checkout Transparente apenas com Cartão](https://github.com/CarlosWGama/php-pagseguro/tree/master/examples/compra/checkout-transparente/compra_cartao.php)
[Checkout Transparente apenas com Debito](https://github.com/CarlosWGama/php-pagseguro/tree/master/examples/compra/checkout-transparente/compra_debito.php)
[Checkout Transparente apenas com Boleto](https://github.com/CarlosWGama/php-pagseguro/tree/master/examples/compra/checkout-transparente/compra_boleto.php)
[Tratando Notififcação](https://github.com/CarlosWGama/php-pagseguro/tree/master/examples/compra/)

---
**Autor:**  Carlos W. Gama *(carloswgama@gmail.com)*
**Licença:** MIT

Livre para usar, modificar como desejar e destribuir como quiser