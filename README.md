# PHP - Pagseguro V2
Classe para realizar compras normais ou recorrentes no PagSeguro

*Para realizar compras recorrentes no PagSeguro como assinaturas, olhe os examples na página abaixo:*

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

## Documentação do uso da classe

[Documentação Pagamentos Recorrentes com PagSeguro](https://github.com/CarlosWGama/php-pagseguro/tree/master/examples/assinatura)

[Documentação Pagamentos comuns com PagSeguro](https://github.com/CarlosWGama/php-pagseguro/tree/master/examples/compra)


## Exemplos

Abaixo segue apenas dois exemples do uso da Biblioteca


### Criando uma compra simples
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

### Criando um plano de assinatura

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

Nos links acima você poderá ver diversos exemples para criar plano, assinatura, compra, notificações... 

---
**Autor:**  Carlos W. Gama *(carloswgama@gmail.com)*
**Licença:** MIT

Livre para usar, modificar como desejar e destribuir como quiser