# PHP - Pagseguro
Classe para realizar ASSINATURAS no PagSeguro

*O PagSeguro tem sua própria classe para pagamentos comuns, disponiveis no link:*

[Compra comum no PagSeguro](https://pagseguro.uol.com.br/v2/guia-de-integracao/api-de-pagamentos.html#!rmcl)

Para realizar assinatura do PagSeguro sem usar nenhuma passe, pode olhar a documentação do PagSeguro

[Documentação do PagSeguro - Compra sem usar Classe](http://download.uol.com.br/pagseguro/docs/pagseguro-assinatura-automatica.pdf)

-----
Esse código é exclusivos para assinaturas ou compras recursisvas. 

### Gerando Token

Para gerar o Token da sua conta do PagSeguro, logar na conta, ir em [Minha Conta >> Preferências >> Integração](https://pagseguro.uol.com.br/preferencias/integracoes.jhtml) e solicar para gerar um token

![Token em produção](http://carloswgama.com.br/pagseguro/pagseguro_gerar_token.jpg)

No Sandbox para testes, seu token pode ser acessado em [Perfil de Integração >> Vendedor](https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html)

![Token no Sandbox](http://carloswgama.com.br/pagseguro/pagseguro_gerar_token_sandbox.jpg)

### Baixando o projeto

Para usar esse projeto, basta baixar esse repositório em seu projeto e importar a classe em src/PagSeguro.php ou usar o composer que é o mais indicado:

```
composer require carloswgama/php-pagseguro:1.*
```

Caso seu projeto já possua um arquivo composer.json, você pode também adiciona-lo nas dependências require e rodar um composer install:
```
{
    "require": {
        "carloswgama/php-pagseguro": "1.*"
    }
}
```

### Criando uma assinatura
``` php
<?php
use CWG\PagSeguro\PagSeguro;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguro($email, $token, $sandbox);

//Nome do comprador
$pagseguro->setNomeCliente("Carlos W. Gama");	
//Email do comprovador
$pagseguro->setEmailCliente("c73062863531198591643@sandbox.pagseguro.com.br");
//Código usado pelo vendedor para identificar qual é a compra
$pagseguro->setReferencia("CWG001");	
//Descrição
$pagseguro->setRazao("-CWG-");		
//Valor cobrado
$pagseguro->setValor('10.00');			
//Local para o comprador será redicionado após a compra com o código (code) identificador da assinatura
$pagseguro->setRedirectURL('http://localhost/pagseguro/callback.php');	
//A pessoa é cobrada em quantos meses? 
$pagseguro->setPeriodicidade(1);		
//0 (WEEKLY - Semanalmente)	| 1 (MONTHLY - Mensalmente) | 2 (BIMONTHLY - Bimestralmente) | 3 (TRIMONTHLY - Trimestralmente) | 6 (SEMIANNUALLY - Semestralmente) | 12 (YEARLY - Anualmente)
try {
    $url = $pagseguro->gerarSolicitacaoPagSeguro();
    print_r($url);	//URL para realizar pagamento
} catch (Exception $e) {
    echo $e->getMessage();
}

```

### Buscando
``` php
<?php
use CWG\PagSeguro\PagSeguro;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguro($email, $token, $sandbox);

$code = $_GET['code']; 

try {
//Consulta por CÓDIGO do PagSeguro
print_r($pagseguro->consultarAssinatura($code));

//Consulta por intervalo
print_r($pagseguro->consultarAssinaturaPeriodo('2016-06-13 00:00', '2016-06-14 17:30'));

//Buscar código pelo valor passado na referencia
echo $code = $pagseguro->getPreApprovalCodeByVenda("CWG002", '2016-06-14 00:00', '2016-06-14 17:30');
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### Cancelando
``` php
<?php
use CWG\PagSeguro\PagSeguro;

$email = "carloswgama@gmail.com";
$token = "33D43C3F884E4EB687C2C62BB92ECD6A";
$sandbox = true;

$pagseguro = new PagSeguro($email, $token, $sandbox);

$code = '5B87B45F7676F61CC42CFFA0175BF7AE';
try {
    print_r($pagseguro->suspenderAssinatura($code));
} catch (Exception $e) {
    echo $e->getMessage();
} 
```
---
**Autor:**  Carlos W. Gama *(carloswgama@gmail.com)*
**Licença:** MIT

Livre para usar, modificar como desejar e destribuir como quiser