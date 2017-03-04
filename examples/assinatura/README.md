# PHP - Pagseguro V2
Classe para realizar ASSINATURAS no PagSeguro com e sem Checkout Transparente

*Para realizar compras comum sem recorrencia, olhe os examples na página abaixo:*

[Compra comum no PagSeguro](https://github.com/CarlosWGama/php-pagseguro/tree/2.1.0/examples/compra)

Para realizar assinatura do PagSeguro sem usar nenhuma classe, apenas através da api REST use a documentação abaixo: 

[Documentação do PagSeguro Pagamento Recorrente Transparente - Compra sem usar Classe](http://download.uol.com.br/pagseguro/docs/pagamento-recorrente-transparente.pdf)

[Documentação do PagSeguro Pagamento Recorrente Padrão - Compra sem usar Classe](http://download.uol.com.br/pagseguro/docs/pagamento-recorrente.pdf)

-----
Esse código é exclusivos para assinaturas ou compras recursisvas e foi criado usando a API das documentações acima. 

## Gerando Token

Para gerar o Token da sua conta do PagSeguro, logar na conta, ir em [Minha Conta >> Preferências >> Integração](https://pagseguro.uol.com.br/preferencias/integracoes.jhtml) e solicar para gerar um token

![Token em produção](http://carloswgama.com.br/pagseguro/pagseguro_gerar_token.jpg)

No Sandbox para testes, seu token pode ser acessado em [Perfil de Integração >> Vendedor](https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html)

![Token no Sandbox](http://carloswgama.com.br/pagseguro/pagseguro_gerar_token_sandbox.jpg)

## Baixando o projeto

Para usar esse projeto, basta baixar esse repositório em seu projeto e importar a classe em src/PagSeguroAssinaturas.php ou usar o composer que é o mais indicado:

```
composer require carloswgama/php-pagseguro:2.*
```

Caso seu projeto já possua um arquivo composer.json, você pode também adiciona-lo nas dependências require e rodar um composer install:
```
{
    "require": {
        "carloswgama/php-pagseguro": "2.*"
    }
}
```

## Criando um Plano

A nova versão da API do PagSeguro usa e recomenda o uso de planos para as assinaturas. Nesse caso vamos criar um plano, onde uma ou mais pessoas podem realizar a assinatura

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
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

```

## Fazendo uma assinatura simples sem checkout transparente

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
use CWG\PagSeguro\PagSeguroAssinaturas;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroAssinaturas($email, $token, $sandbox);

$codigoPlano = 'E488FBA13434E41114179FB619875F62';
$url = $pagseguro->assinarPlanoCheckout($codigoPlano);

echo 'URL para o Checkout: ' . $url;
``` 

Este é o meio mais simples de criar uma assinatura, basta ter um plano já criado e informar o código do plano. Porém ele não vincula nenhuma informação do seu cliente a assinatura com oo código de referencia por exemplo. 

**Deste modo eu não recomendo o uso do checkout padrão para assinaturas caso precise identificar qual o cliente do seu sistema que está assinando. OU criar um plano diferente para cada cliente, de modo a poder identificar quem é o cliente pela referencia do plano**   

## Fazendo assinatura com checkout transparente

O Checkout transparente é aquele que ocorre no próprio ambiente do serviço e não no PagSeguro. 

Para realizá-lo é necessário antes buscar um hash que identifica o seu cliente e gerar o token do cartão do cliente (Mais detalhes podem ser visto na documentação do PagSeguro)

No código abaixo podemos usar o método preparaCheckoutTransparente() para gerar todo javascript necessário:

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
use CWG\PagSeguro\PagSeguroAssinaturas;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroAssinaturas($email, $token, $sandbox);

//Sete apenas TRUE caso queira importa o Jquery também. Caso já possua, não precisa
$js = $pagseguro->preparaCheckoutTransparente(true);

echo $js['completo']; //Importa todos os javascripts necessários
?>

<h2> Campos Obrigatórios </h2>

<p>Número do Cartão</p>
<!-- OBRIGATÓRIO UM CAMPO COM O ID pagseguro_cartao_numero-->
<input type="text" id="pagseguro_cartao_numero" value="4111111111111111"/>

<p>CVV do cartão</p>
<!-- OBRIGATÓRIO UM CAMPO COM O ID pagseguro_cartao_cvv-->
<input type="text" id="pagseguro_cartao_cvv" value="123"/>

<p>Mês de expiração do Cartao</p>
<!-- OBRIGATÓRIO UM CAMPO COM O ID pagseguro_cartao_mes-->
<input type="text" id="pagseguro_cartao_mes" value="12"/>

<p>Ano de Expiração do Cartão</p>
<!-- OBRIGATÓRIO UM CAMPO COM O ID pagseguro_cartao_ano-->
<input type="text" id="pagseguro_cartao_ano" value="2030"/>

<br/>

<button id="botao_comprar">Comprar</button>

<script type="text/javascript">

    //Gera os conteúdos necessários
    $('#botao_comprar').click(function() {
        PagSeguroBuscaHashCliente(); //Cria o Hash identificador do Cliente usado na transição
        PagSeguroBuscaBandeira();   //Através do pagseguro_cartao_numero do cartão busca a bandeira
        PagSeguroBuscaToken();      //Através dos 4 campos acima gera o Token do cartão  
        setTimeout(function() {
            enviarPedido();
        }, 3000);
    });

    function enviarPedido() {
        /** FAÇA O QUE QUISER DAQUI PARA BAIXO **/
        alert($('#pagseguro_cliente_hash').val())
        alert($('#pagseguro_cartao_token').val())
        
        var data = {
            hash:  $('#pagseguro_cliente_hash').val(),
            token: $('#pagseguro_cartao_token').val()
        };
        
        $.post('http://localhost/pagseguro/examples/assinando2.php', data, function(response) {
            alert(response);
        });
    }
</script>
```
Nesse exemplo acima, nós precisamos obrigatóriamente dos seguintes campos:

| Campo ID                | Descrição                                           | Exemplo          |
|-------------------------|-----------------------------------------------------|------------------|
| pagseguro_cartao_numero | Informa o número do cartão                          | 4111111111111111 |
| pagseguro_cartao_cvv    | Informa o código de segurança atrás do cartão (CVV) | 123              |
| pagseguro_cartao_mes    | Informa o mês que o cartão expira                   | 12               |
| pagseguro_cartao_ano    | Informa o ano que o cartão expira                   | 2030             |

Além destes campos, o javascript irá criar 3 novos campos:

| Campo ID                  | Descrição                                                                                                                       |
|---------------------------|---------------------------------------------------------------------------------------------------------------------------------|
| pagseguro_cliente_hash    | Cria um Hash unico para identificar o cliente com a compra com checkout transparente. Será usado na segunda parte da assinatura |
| pagseguro_cartao_token    | Cria um Token unico com todas informações necessárias do cartão do cliente. Será usado na segunda parte da assinatura.          |
| pagseguro_cartao_bandeira | Identifica qual é a bandeira do cartão do cliente. É usado apenas para gerar o pagseguro_cartao_token, portanto deve ser gerado antes do token                           |


O campo pagseguro_cliente_hash e pagseguro_cartao_token são usados na solicitação de assinar um plano na parte seguinte. E esses dados são gerados através das funções javascripts:

| Campo ID                  | Função Responsável por Gerar |
|---------------------------|------------------------------|
| pagseguro_cliente_hash    | PagSeguroBuscaHashCliente(); |
| pagseguro_cartao_token    | PagSeguroBuscaToken();       |
| pagseguro_cartao_bandeira | PagSeguroBuscaBandeira();    |

Após ter em mão o token do cartão e o hash do cliente, você pode solicitar a assinatura normalmente. No exemplo acima esses dados foram enviado via ajax, mas use da melhor forma na sua aplicação.

### Concluindo a adesão a Assinatura
``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
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
    $codigoAssinatura = $pagseguro->assinaPlano();
    echo 'O código unico da assinatura é: ' . $codigoAssinatura;
} catch (Exception $e) {
    echo $e->getMessage();
}
```

Caso não possua alguma das informações acima, pode busca-lo na etapa anterior junto com os dados do cartão do cliente.

**O Código da assinatura é único paa cada assinatura. Com ele você pode buscar informações de quem é o cliente pela referencia ou o estatus da assinatura, então lembre-se de guarda-lo**

## Consultando Notificação

Sempre que uma assinatura é criada ou renovada, ela envia uma notificação para o link que estiver configurando no ambiente do PagSeguro, com isso é possível acessar as informações da assinatura pelo código da notificação enviado para fazer ativar as funcionalidades em seu site para o cliente:

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
use CWG\PagSeguro\PagSeguroAssinaturas;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroAssinaturas($email, $token, $sandbox);

//Caso seja uma notificação de uma assinatura (preApproval)
if ($_POST['notificationType'] == 'preApproval') {
    $codigoNotificacao = $_POST['notificationCode']; //Recebe o código da notificação e busca as informações de como está a assinatura
    $response = $pagseguro->consultarNotificacao($codigoNotificacao);
    print_r($response); //Aqui é possível obter informações como se a assinatura está ativa ou não
}
```
Para alterar a url de notificação basta acessar:
[Sandbox: Perfis de Integração >> Vendedor >> Notificação de Transação](https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html)
[Produção: Minha Conta >> Preferências >> Integrações >> Notiifcação de Transação](https://pagseguro.uol.com.br/preferencias/integracoes.jhtml)


## Consultando Assinatura

Além da notificação você também pode consultar o estatus de uma assinatura direto pelo código da assinatura
``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
use CWG\PagSeguro\PagSeguroAssinaturas;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroAssinaturas($email, $token, $sandbox);

$codeAssinatura = '131F919E44449D62244DEFAA0FEB334C';
$response = $pagseguro->consultaAssinatura($codeAssinatura);
print_r($response);
```
Esse código também pode ser encontrado no ambiente do PagSeguro na seção de assinaturas ([Sandbox: Assinaturas>>Assinaturas](https://sandbox.pagseguro.uol.com.br/assinaturas.html) | [Produção: Minha Conta >> Pagamento Recorrentes >> Adesões](https://pagseguro.uol.com.br/pre-aprovacoes/lista.html))


## Cancelando Assinatura

Cancela definitvamente a assinatura. Essa ação não pode ser desfeita

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
use CWG\PagSeguro\PagSeguroAssinaturas;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroAssinaturas($email, $token, $sandbox);

$codePagSeguro = '324CE6D30505CFB3344E1F8C5CFF9926';

try {
    print_r($pagseguro->cancelarAssinatura($codePagSeguro));
} catch (Exception $e) {
    echo $e->getMessage();
}

```

## Suspendendo Assinatura

A suspensão é semelhante a cancelar uma assinatura, porém ela permite que a assinatura volte a ser habilitada.

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
use CWG\PagSeguro\PagSeguroAssinaturas;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroAssinaturas($email, $token, $sandbox);

$codePagSeguro = '1BA8C57CD4D4F3F114A8FFB47768EA2F';

try {
    print_r($pagseguro->setHabilitarAssinatura($codePagSeguro, false));
} catch (Exception $e) {
    echo $e->getMessage();
}

```

## Habilita Assinatura Suspensa

Reativa assinatura suspensa

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
use CWG\PagSeguro\PagSeguroAssinaturas;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroAssinaturas($email, $token, $sandbox);

$codePagSeguro = '1BA8C57CD4D4F3F114A8FFB47768EA2F';

try {
    print_r($pagseguro->setHabilitarAssinatura($codePagSeguro, true));
} catch (Exception $e) {
    echo $e->getMessage();
}

```
---
**Autor:**  Carlos W. Gama *(carloswgama@gmail.com)*
**Licença:** MIT

Livre para usar, modificar como desejar e destribuir como quiser