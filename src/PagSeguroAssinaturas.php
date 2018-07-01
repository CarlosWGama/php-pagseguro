<?php

namespace CWG\PagSeguro;

/**
* @package Library
* @category Pagamento
* @author Carlos W. Gama (carloswgama@gmail.com)
* @license MIT
* @version 2.1.0
* @since 2.0.0
* Classe de pagamento de Recursivo/Assinaturas no PagSeguro
*/
class PagSeguroAssinaturas {
	
	//===================================================
	// 					URL
	//===================================================
	/**
	* URL para a API em produção
	* @access private
	* @var string
	*/
	private $urlAPI = 'https://ws.pagseguro.uol.com.br/';

	/**
	* URL para o pagamento em produção
	* @access private
	* @var string
	*/
	private $urlPagamento = 'https://pagseguro.uol.com.br/v2/pre-approvals/request.html?code=';

	/**
	* URL para a API em Sandbox
	* @access private
	* @var string
	*/
	private $urlAPISandbox = 'https://ws.sandbox.pagseguro.uol.com.br/';

	/**
	* URL para o pagamento em Sandbox
	* @access private
	* @var string
	*/
	private $urlPagamentoSandbox = 'https://sandbox.pagseguro.uol.com.br/v2/pre-approvals/request.html?code=';

	/**
	* Verifica se é Sanbox ou em Produção
	* @access private
	* @var bool
	*/
	private $isSandbox = false;

	//===================================================
	// 					Dados da Compra
	//===================================================
	/**
	* O nome e mail do cliente | Deve ser um nome composto
	* @access private
	* @var array
	*/
	private $cliente = array(
		'email' => '',
		'name'	=> '',
		'hash'	=> '',
		'phone' => array(
			'areaCode' 	=> '',
			'number'	=> ''
		),
		'documents' => array(
			0	=> array(
				'type'	=> "CPF",
				'value'	=> ''
			)
		),
		'address' => array(
			'street'		=> '',
			'number'		=> '',
			'complement'	=> '',
			'district'		=> '',
			'city'			=> '',
			'state'			=> '',
			'country'		=> 'BRA',
			'postalCode'	=> ''
		)
	);


	private $formaPagamento = array(
		'type'			=> 'CREDITCARD',
		'creditCard'	=> array(
			'token'		=> '',
			'holder'	=> array(
				'name'			=> '',
				'birthDate'		=> '',
				'phone' => array(
					'areaCode' 	=> '',
					'number'	=> ''
				),
				'documents' => array(
					0	=> array(
						'type'	=> "CPF",
						'value'	=> ''
					)
				),
				'billingAddress' => array(
					'street'		=> '',
					'number'		=> '',
					'complement'	=> '',
					'district'		=> '',
					'city'			=> '',
					'state'			=> '',
					'country'		=> 'BRA',
					'postalCode'	=> ''
				)
			)
		)
	);

	/**
	* Um ID qualquer para identificar qual é a compra no sistema 
	* @access private
	* @var string
	*/
	private $referencia = '';

	/**
	* Descricao da compra
	* @access private
	* @var string
	*/
	private $descricao = ' CWG ';

	/**
	* Valor cobrado
	* @access private
	* @var float
	*/
	private $valor = 0.00;

	/**
	* Periodicidade
	* @access private
	* @var string 'WEEKLY'|'MONTHLY'|'BIMONTHLY'|'TRIMONTHLY'|'SEMIANNUALLY'|'YEARLY'
	*/
	private $periodicidade = 'MONTHLY';

	/** PERIODIIDADE **/
	const SEMANAL = 'WEEKLY';
	const MENSAL = 'MONTHLY';
	const BIMESTRAL = 'BIMONTHLY';
	const TRIMESTRAL = 'TRIMONTHLY';
	const SEMESTRAL = 'SEMIANNUALLY';
	const ANUAL = 'YEARLY';

	/**
	* Link para onde a pessoa será redicionada após concluir a assinatura no Pagseguro
	* @access private
	* @var string (url)
	*/
	private $redirectURL = '';

	/**
	* Link para onde será enviada as notificações a cada alteração na compra
	* @access private
	* @var string (url)
	*/
	private $notificationURL = '';

	/**
	* Código do PagSeguro referente a assinatura
	* @access private
	* @var string (url)
	*/
	private $preApprovalCode = '';

	/**
	* Código do Plano criado
	* @access private
	* @var string
	*/
	private $planoCode;

	//===================================================
	// 					OPCIONAIS PARA PLANOS
	//===================================================
	/**
	* Após quanto tempo de contratado a assinatura expira
	* @acces private
	* @var array
	*/
	private $expiracao = array(
		'value' => 1000000,
		'unit'	=> 'YEARS' //YEARS|MONTHS|DAYS
	);

	/**
	* URL para a página para onde o usuário é enviado ao solicitar o cancelamento da assinatura no pagseguro
	* @var string
	*/
	private $URLCancelamento = '';


	/**
	* Informa o máximo de usuários que podem usar o plano (Opcional | Deixar 0 para nõa ter limite)
	* @access private
	* @var int
	*/
	private $maximoUsuarios = 0;


	/** 
	* Headers para acesso a API do gerarSolicitacaoPagSeguro
	* @access private
	* @var array
	*/
	private $headers = array(
		'Content-Type: application/json', 
		'Accept: application/vnd.pagseguro.com.br.v3+json;charset=ISO-8859-1'
	);

	//===================================================
	// 					Credencias
	//===================================================

	/**
	* Email do vendedor do PagSeguro
	* @access private
	* @var string
	*/
	private $email;

	/**
	* token do vendedor do PagSeguro
	* @access private
	* @var string
	*/
	private $token;

	// ================================================================
	// API Assinatura PagSeguro
	// ================================================================
	/**
	* Construtor
	* @param $email string
	* @param $token string
	* @param isSandbox bool (opcional | Default false)
	*/
	public function __construct($email, $token, $isSandbox = false) {
		$this->email 		= $email;
		$this->token 		= $token;
		$this->isSandbox 	= $isSandbox;
	}

	/**
	* Criar um novo plano
	*/
	public function criarPlano() {
		
		//Dados da assinatura
		$dados['reference']							= $this->referencia;
		$dados['preApproval']['charge'] 			= 'auto';
		$dados['preApproval']['name']				= $this->referencia;
		$dados['preApproval']['details']			= $this->descricao;
		$dados['preApproval']['amountPerPayment']	= $this->valor;
		$dados['preApproval']['period']				= $this->periodicidade;
		$dados['preApproval']['expiration']			= $this->expiracao;
		
		//Opcionais
		if (!empty($this->URLCancelamento)) 
			$dados['preApproval']['cancelURL']		= $this->URLCancelamento;
		
		if (!empty($this->redirectURL)) 
			$dados['redirectURL']	= $this->redirectURL;

		// if (!empty($this->notificationURL)) 
			// $dados['notificationURL']	= $this->notificationURL;
		

		if ($this->maximoUsuarios > 0) 
			$dados['maxUses']						= $this->maximoUsuarios;

		$response = $this->post($this->getURLAPI() . 'pre-approvals/request', $dados);
		
		if ($response['http_code'] == 200) {
			return $response['body']['code'];
		} else {
			print_r($response);die;
			throw new \Exception(current($response['body']['errors']));
		}
	}

	/** Cria um ID para comunicação com Checkout Transparente 
	* @return id string
	*/
	public function iniciaSessao() {
		$url = $this->getURLAPI().'v2/sessions/' . $this->getCredenciais();
		$curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        @curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$xml = curl_exec($curl);
		curl_close($curl);		

		//Problema Token do vendedor
		if ($xml == 'Unauthorized') {
			throw new \Exception("Token inválido");
		}

		$xml = simplexml_load_string($xml);
		return $xml->id;
	}

	/**
	* GEra todo o JavaScript necessário
	*/
	public function preparaCheckoutTransparente($importaJquery = false) {
		$sessionID = $this->iniciaSessao();

		$javascript = array();

		//Jquery
		if ($importaJquery) 
			$javascript['jquery'] = '<script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8="  crossorigin="anonymous"></script>';

		//Sessão
		if ($this->isSandbox)
			$javascript['principal'] = '<script type="text/javascript" src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>';
		else
			$javascript['principal'] = '<script type="text/javascript" src="https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>';

		$javascript['principal'] .= '<script type="text/javascript">PagSeguroDirectPayment.setSessionId("' . $sessionID . '")</script>';

		//Indentificação do comprador
		$javascript['cliente_hash'] = "
			<input type='hidden' id='pagseguro_cliente_hash'/>
			<script type='text/javascript'>
				function PagSeguroBuscaHashCliente() {
					$('#pagseguro_cliente_hash').val(PagSeguroDirectPayment.getSenderHash());
					console.log('Hash Cliente: ' + PagSeguroDirectPayment.getSenderHash());
				}
			</script> \n
		";

		//Obter bandeira
		$javascript['bandeira'] = "
			<input type='hidden' id='pagseguro_cartao_bandeira' />
			<script type='text/javascript'>
				function PagSeguroBuscaBandeira() {
					PagSeguroDirectPayment.getBrand({cardBin: $('#pagseguro_cartao_numero').val(),
						success: function(response) { console.log('Bandeira: ' + response.brand.name); $('#pagseguro_cartao_bandeira').val(response.brand.name)},
						error: function(response) { console.log(response); },
					});
				}
			</script>";

		//Obter token do Cartão
		$javascript['token'] = "
		<input type='hidden' id='pagseguro_cartao_token' />
		<script type='text/javascript'>
			function PagSeguroBuscaToken() {
				PagSeguroDirectPayment.createCardToken({
					cardNumber: $('#pagseguro_cartao_numero').val(),
					brand: $('#pagseguro_cartao_bandeira').val(),
					cvv: $('#pagseguro_cartao_cvv').val(),
					expirationMonth: $('#pagseguro_cartao_mes').val(),
					expirationYear: $('#pagseguro_cartao_ano').val(),
					success: function(response) { console.log('Token: ' + response.card.token); $('#pagseguro_cartao_token').val(response.card.token)},
					error: function(response) { console.log(response); },
				});
			}
		</script>";

		$javascript['completo'] = implode(' ', $javascript);
		return $javascript;
	}	
	
	/**
	* Inicia um pedido de compra
	* @access public
	* @return array (url para a compra e código da compra)
	*/
	public function assinaPlano() {

		//Dados do cliente
		$dados['sender'] 		= $this->cliente;
		//Dados do pagamento
		$dados['paymentMethod'] = $this->formaPagamento;
		//Dados do plano
		$dados['plan'] 			= $this->planoCode;
		//Dados da compra
		$dados['reference']		= $this->referencia;

		$response = $this->post($this->getURLAPI() . 'pre-approvals/', $dados);

		if ($response['http_code'] == 200) {
			return $response['body']['code'];
		} else {
			throw new \Exception(current($response['body']['errors']));
		}	
	}

	/**
	* Realiza assinatura do plano pelo ambiente checkout padrão
	*/
	public function assinarPlanoCheckout($planoCode) {
		return $this->getURLPagamento() . $planoCode;
	}
 
	/** Realiza uma consulta a notificação **/
	public function consultarNotificacao($codePagSeguro) {
		$response = $this->get($this->getURLAPI() . 'pre-approvals/notifications/' . $codePagSeguro);

		if ($response['http_code'] == 200) {
			return $response['body'];
		} else {
			throw new \Exception(current($response['body']['errors']));
		}
	}

	/** Consulta uma assinatura **/
	public function consultaAssinatura($codePagSeguro) {
		$response = $this->get($this->getURLAPI() . 'pre-approvals/' . $codePagSeguro);

		if ($response['http_code'] == 200) {
			return $response['body'];
		} else {
			throw new \Exception(current($response['body']['errors']));
		}
	}
 
	/**
	* Cancela a assinatura
	* @access public
	* @param $codePagSeguro string (Código fornecido pelo pagseguro para uma compra)
	* @return bool
	*/
	public function cancelarAssinatura($codePagSeguro) {
		$response = $this->put($this->getURLAPI() . 'pre-approvals/' . $codePagSeguro . '/cancel');

		if ($response['http_code'] == 204) {
			return true;
		} else {
			throw new \Exception(current($response['body']['errors']));
		}
	}

	/**
	* Habilita ou Desabilita uma assinatura
	* @access public
	* @param $codePagSeguro $codigoPreApproval
	* @param $habilitar bool
	*/
	public function setHabilitarAssinatura($codePagSeguro, $habilitar = true) {
		$dados['status'] = ($habilitar? 'ACTIVE' : 'SUSPENDED'); 
		$response = $this->put($this->getURLAPI() . 'pre-approvals/' . $codePagSeguro . '/status', $dados);
		
		if ($response['http_code'] == 204) {
			return true;
		} else {
			throw new \Exception(current($response['body']['errors']));
		}
	}

	
	// =================================================================
	// Util
	// =================================================================
	/**
	* Formata a credêncial do pagseguro
	* @access private
	* @return array(email, token)
	*/
	private function getCredenciais() {
		$dados['email'] = $this->email;
		$dados['token'] = $this->token;

		return '?' . http_build_query($dados);
	}

	/**
	* Busca a URL da API de acordo com a opção Sandbox
	* @access private
	* @return string url
	*/
	private function getURLAPI() {
		return ($this->isSandbox ? $this->urlAPISandbox : $this->urlAPI);
	}

	/**
	* Busca a URL de Pagamento de acordo com a opção Sandbox
	* @access private
	* @return string url
	*/
	private function getURLPagamento() {
		return ($this->isSandbox ? $this->urlPagamentoSandbox : $this->urlPagamento);
	}
	// =================================================================
	// GET e SET
	// =================================================================
	 
	/**
	* @param $emailCliente string
	*/
	public function setEmailCliente($emailCliente) {
	    return $this->cliente['email'] = $emailCliente;
	}

	 
	/**
	* @param $referencia string
	*/
	public function setReferencia($referencia) {
	    return $this->referencia = $referencia;
	}
	 
	/**
	* @param $razao string
	*/
	public function setDescricao($descricao) {
	    return $this->descricao = $descricao;
	}
	 
	/**
	* @param $valor float
	*/
	public function setValor($valor) {
	    return $this->valor = number_format($valor, 2, '.', '');
	}

	/**
	* @param $periodicidade int | string('WEEKLY', 'MONTHLY', 'BIMONTHLY', 'TRIMONTHLY', 'SEMIANNUALLY', 'YEARLY')
	*/
	public function setPeriodicidade($periodicidade) {
		$this->periodicidade = $periodicidade;

		//Tratamento
		if (!in_array($this->periodicidade, array('WEEKLY', 'MONTHLY', 'BIMONTHLY', 'TRIMONTHLY', 'SEMIANNUALLY', 'YEARLY')))
			$this->periodicidade = '-'; //Erro

	    return $this->periodicidade;
	}
	 
	/**
	* @param $redirectURL string
	*/
	public function setRedirectURL($redirectURL) {
	    return $this->redirectURL = $redirectURL;
	}

	/**
	* @return string
	*/
	public function setNotificationURL($url) {
		$this->notificationURL = $url;
	}
	
	/**
	* @param $preApprovalCode string
	*/
	public function setPreApprovalCode($preApprovalCode) {
	    return $this->preApprovalCode = $preApprovalCode;
	}

	/**
	* Muda o periodo para o plano expirar sozinho após contratado
	* @param $periodo int
	* @param $unidade string
	*/
	public function setExpiracao($periodo, $unidade) {
		if (!is_int($periodo)) $periodo = 1000000;
		if (!in_array($unidade, array('YEARS', 'MONTHS', 'DAYS'))) $unidade = 'YEARS';

		$this->expiracao = array(
			'value'	=> $periodo,
			'unit'	=> $unidade
		);
	}

	/**
	* Seta a url para onde o usuário é enviado para cancelar a assinatura
	* @param $url string
	*/
	public function setURLCancelamento($url) {
		$this->URLCancelamento = $url;
	}

	/**
	* Informa o máximo de usuários a usar o plano
	*/
	public function setMaximoUsuariosNoPlano($valor) {
		$this->maximoUsuarios = intval($valor);
	}

	
	/**
	* @param $preApprovalCode string
	*/
	public function setPlanoCode($planoCode) {
	    return $this->planoCode = $planoCode;
	}

	/**
	* @param $hash string
	*/
	public function setHashCliente($hash) {
		$this->cliente['hash'] = $hash;
	}

		/**
	* @param $nomeCliente string
	*/
	public function setNomeCliente($nomeCliente) {
	    $this->cliente['name'] = $nomeCliente;
		$this->formaPagamento['creditCard']['holder']['name'] = $nomeCliente;
	}

	/**
	* Seta o dia de nascimento do cliente
	* @param $ano (dd/MM/YYYY)
	*/
	public function setNascimentoCliente($ano) {
		$this->formaPagamento['creditCard']['holder']['birthDate'] = $ano;
	}

	
	/** Seta o CPF do Cliente **/
	public function setCPF($numero) {
		$this->cliente['documents'][0]['value'] = $numero;
		$this->formaPagamento['creditCard']['holder']['documents'][0]['value'] = $numero;
	}

	/**
	* @param $ddd int
	* @param $numero int
	*/
	public function setTelefone($ddd, $numero) {
		$this->cliente['phone']['areaCode'] = $ddd;
		$this->cliente['phone']['number'] = $numero;

		$this->formaPagamento['creditCard']['holder']['phone']['areaCode'] = $ddd;
		$this->formaPagamento['creditCard']['holder']['phone']['number'] = $numero;
	}

	/** Seta o token do Cartão **/
	public function setTokenCartao($token) {
		$this->formaPagamento['creditCard']['token'] = $token;
	}

	public function setEnderecoCliente($rua, $numero, $complemento, $bairro, $cidade, $estado, $cep) {
		$this->formaPagamento['creditCard']['holder']['billingAddress'] = $this->cliente['address'] = array(
			'street'		=> $rua,
			'number'		=> $numero,
			'complement'	=> $complemento,
			'district'		=> $bairro,
			'city'			=> $cidade,
			'state'			=> $estado,
			'country'		=> 'BRA',
			'postalCode'	=> $cep
		);		
	}

	/********** REST ******************/
	/**
	* Realiza uma requisição GET
	* @access private
	* @param $url string
	* @return array
	*/
	private function get($url) {
		$url .= $this->getCredenciais();

		$curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
		$curl_response = curl_exec($curl);
        $response = curl_getinfo($curl);
        $response['body'] = json_decode($curl_response, true);
        curl_close($curl);

        return $response;

	}

	/**
	* Realiza uma requisição POST
	* @access private
	* @param $url string
	* @param $data array
	* @return array
	*/
	private function post($url, $data = array()) {
		$url .= $this->getCredenciais();

		$curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");  
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        @curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
		if (!empty($data))
        	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $curl_response = curl_exec($curl);

        $response = curl_getinfo($curl);
        $response['body'] = json_decode($curl_response, true);
        curl_close($curl);

        return $response;
	}

	/**
	* Realiza uma requisição PUT
	* @access private
	* @param $url string
	* @param $data array
	* @return array
	*/
	private function put($url, $data = array()) {
		$url .= $this->getCredenciais();

		$curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");  
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        @curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
		if (!empty($data))
        	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $curl_response = curl_exec($curl);
        $response = curl_getinfo($curl);
        $response['body'] = json_decode($curl_response, true);
        curl_close($curl);

        return $response;
	}

}