# PHP - Pagseguro V2
Classe para realizar compras sem recorrencia no PagSeguro

*Para realizar compras recorrentes no PagSeguro como assinaturas, olhe os examples na página abaixo:*

[Pagamentos Recorrentes com PagSeguro](https://github.com/CarlosWGama/php-pagseguro/tree/2.1.0/examples)

[Documentação do PagSeguro Pagamento Padrão - Compra sem usar Classe](https://dev.pagseguro.uol.com.br/documentacao/pagamentos/pagamento-padrao)

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

## Criando uma compra

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
use CWG\PagSeguro\PagSeguroCompras;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroCompras($email, $token, $sandbox);

//Nome do comprador (OPCIONAL)
$pagseguro->setNomeCliente("CARLOS W GAMA");	
//Email do comprovador (OPCIONAL)
$pagseguro->setEmailCliente("c73062863531198591643@sandbox.pagseguro.com.br");
//Código usado pelo vendedor para identificar qual é a compra (OPCIONAL)
$pagseguro->setReferencia("CWG004");	
//Adiciona os itens da compra (ID do ITEM, DESCRICAO, VALOR, QUANTIDADE)
$pagseguro->adicionarItem('ITEM0001', 'Item 1', 10.00, 2);
$pagseguro->adicionarItem('ITEM0002', 'Item 2', 15.50, 1);

//URL para onde será enviado as notificações de alteração da compra (OPCIONAL)
$pagseguro->setNotificationURL('http://carloswgama.com.br/pagseguro/not/notificando.php');
//URL para onde o comprador será redicionado após a compra (OPCIONAL)
$pagseguro->setRedirectURL('http://carloswgama.com.br/');

try{
    $url = $pagseguro->gerarURLCompra();
    echo 'Sua URL para o pagamento: ' . $url;
} catch (Exception $e) {
    echo $e->getMessage();
}
``` 

## Consultando Notificação

Sempre que uma compra é realizada, ela envia uma notificação para o link que estiver configurando no ambiente do PagSeguro (Ou para o link que tenha sid informado no notificationURL no ato de criar a compra), com isso é possível acessar as informações da assinatura pelo código da notificação enviado para fazer ativar as funcionalidades em seu site para o cliente:

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
use CWG\PagSeguro\PagSeguroCompras;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroCompras($email, $token, $sandbox);

//Caso seja uma notificação de compra (transaction)
if ($_POST['notificationType'] == 'transaction') {
    $codigo = $_POST['notificationCode']; //Recebe o código da notificação e busca as informações de como está a assinatura
    $response = $pagseguro->consultarNotificacao($codigo);
    print_r($response);die;
}
```
Para alterar a url de notificação basta acessar:
[Sandbox: Perfis de Integração >> Vendedor >> Notificação de Transação](https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html)
[Produção: Minha Conta >> Preferências >> Integrações >> Notiifcação de Transação](https://pagseguro.uol.com.br/preferencias/integracoes.jhtml)


## Consultando Compra pelo Còdigo da Transação ou Pela Referencia

``` php
<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
use CWG\PagSeguro\PagSeguroCompras;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguroCompras($email, $token, $sandbox);

echo "<h2>Consulta pelo código de Transação</h2>";
//Pelo cóigo da transação
$codigoTransacao = 'CF748673-0190-4B15-8ACA-A10192E7C1D4'; //È o código gerado no ato da compra pelo PagSeguro
$response = $pagseguro->consultarCompra($codigoTransacao);
print_r($response);

echo "<br/><hr/>";

echo "<h2>Consulta pelo código de Referencia</h2>";
//Pelo Código da Referencia
$referencia = 'CWG004'; //È o código gerado no seu site ao criar a solicitação de compra
$response = $pagseguro->consultarCompraByReferencia($referencia);
print_r($response);
```
---
**Autor:**  Carlos W. Gama *(carloswgama@gmail.com)*
**Licença:** MIT

Livre para usar, modificar como desejar e destribuir como quiser