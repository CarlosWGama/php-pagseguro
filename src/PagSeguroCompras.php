<?php

namespace CWG\PagSeguro;

/**
* @package Library
* @category Pagamento
* @author Carlos W. Gama (carloswgama@gmail.com)
* @license MIT
* @version 2.1.0
* @since 2.1.0
* Classe de pagamento de Recursivo/Assinaturas no PagSeguro
*/
class PagSeguroCompras {
	
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
	private $urlPagamento = 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=';

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
	private $urlPagamentoSandbox = 'https://sandbox.pagseguro.uol.com.br/v2/checkout/payment.html?code=';

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
		'senderName' 	=> '',
		'senderEmail'	=> '',
	);

	/**
	* Lista de itens
	* @var array
	*/
	private $itens = array();

	/**
	* Um ID qualquer para identificar qual é a compra no sistema 
	* @access private
	* @var string
	*/
	private $referencia = '';

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
	* Headers para acesso a API do gerarSolicitacaoPagSeguro
	* @access private
	* @var array
	*/
	private $headers = array(
		'Content-Type: application/x-www-form-urlencoded; charset=ISO-8859-1', 
		'Accept: application/xml;charset=ISO-8859-1'
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

	
	/** Cria um ID para comunicação com Checkout Transparente 
	* @return id string
	*/
	public function iniciaSessao() {
		$auth = '?' . http_build_query($this->getCredenciais());
		$url = $this->getURLAPI('v2/sessions/') . $auth;
		$curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
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
	public function gerarURLCompra() {
		$dados = array();
		//Dados do cliente
		if ($this->cliente)
			$dados = array_merge($this->cliente, $dados);
		//Itens
		
		foreach ($this->itens as $itens) {
			foreach ($itens as $key => $value)
				$dados[$key] = $value;
		}
		
		//Links
		if (isset($this->redirectURL))
			$dados['redirectURL'] = $this->redirectURL;

		if (isset($this->notificationURL))
			$dados['notificationURL'] = $this->notificationURL;


		//Dados da compra
		$dados['reference']		= $this->referencia;
		$dados['currency'] 		= 'BRL';

		$response = $this->post($this->getURLAPI('v2/checkout'), $dados);

		if (isset($response->code)) {
			return $this->getURLPagamento() . $response->code;
		} else {
			throw new \Exception($response);
		}	
	}
 
	/** Realiza uma consulta a notificação **/
	public function consultarNotificacao($codePagSeguro) {
		$response = $this->get($this->getURLAPI('v2/transactions/notifications/'. $codePagSeguro));
		
		if (isset($response->code)) {
			$dados = (array) $response;
			$dados['info'] = $this->getStatusCompra($dados['status']);
			return $dados;
		} else {
			throw new \Exception($response);
		}
	}

	/** Consulta uma compra pelo código da compra **/
	public function consultarCompra($codePagSeguro) {
		$response = $this->get($this->getURLAPI('v2/transactions/' . $codePagSeguro));
		
		if (isset($response->code)) {
			$dados = (array) $response;
			$dados['info'] = $this->getStatusCompra($dados['status']);
			return $dados;
		} else {
			throw new \Exception($response);
		}
	}

	/** Consulta uma compra pela referencia **/
	public function consultarCompraByReferencia($referencia) {
		$response = $this->get($this->getURLAPI('v2/transactions'), array('reference' => $referencia));
		
		if (isset($response->transactions)) {
			$dados = (array) $response;
			$dados['transactions'] = (array) $dados['transactions'];
			
			foreach($dados['transactions']['transaction'] as $key => $value) {
				$dados['transactions']['transaction'][$key] = (array)$value[0];
				$dados['transactions']['transaction'][$key]['info'] = $this->getStatusCompra($dados['transactions']['transaction'][$key]['status']);				
			}
			return $dados;
		} else {
			throw new \Exception($response);
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
		return $dados;
	}

	/**
	* Busca a URL da API de acordo com a opção Sandbox
	* @access private
	* @return string url
	*/
	private function getURLAPI($url = '') {
		return ($this->isSandbox ? $this->urlAPISandbox : $this->urlAPI) . $url;
	}

	/**
	* Busca a URL de Pagamento de acordo com a opção Sandbox
	* @access private
	* @return string url
	*/
	private function getURLPagamento() {
		return ($this->isSandbox ? $this->urlPagamentoSandbox : $this->urlPagamento);
	}

	/**
	* Retorna a URL para criar uma sessão
	* @access private
	* @return string
	*/
	private function getSessionURL() {
		return ($this->isSandbox ? $this->urlSessionAPISandbox : $this->urlSessionAPI);
	}

	/**
	* Retorna uma descrição do estdo da comprA
	* @param $status int
	* @return array
	*/
	public function getStatusCompra($status) {
		$info = array();
		switch($status) {
			case 1: $info = 
				array(
					'estado' 	=> 'Aguardando pagamento',
					'descricao' => 'o comprador iniciou a transação, mas até o momento o PagSeguro não recebeu nenhuma informação sobre o pagamento.'
				 ); break;
			case 2: $info = 
				array(
					'estado' 	=> 'Em análise',
					'descricao' => 'o comprador optou por pagar com um cartão de crédito e o PagSeguro está analisando o risco da transação.'
				 ); break;
			case 3: $info = 
				array(
					'estado' 	=> 'Paga',
					'descricao' => 'a transação foi paga pelo comprador e o PagSeguro já recebeu uma confirmação da instituição financeira responsável pelo processamento..'
				 ); break;
			case 4: $info = 
				array(
					'estado' 	=> 'Disponível',
					'descricao' => 'a transação foi paga e chegou ao final de seu prazo de liberação sem ter sido retornada e sem que haja nenhuma disputa aberta.'
				 ); break;
			case 5: $info = 
				array(
					'estado' 	=> 'Em disputa',
					'descricao' => 'o comprador, dentro do prazo de liberação da transação, abriu uma disputa.'
				 ); break;
			case 6: $info = 
				array(
					'estado' 	=> 'Devolvida',
					'descricao' => 'o valor da transação foi devolvido para o comprador.'
				 ); break;
			case 7: $info = 
				array(
					'estado' 	=> 'Cancelada',
					'descricao' => 'a transação foi cancelada sem ter sido finalizada.'
				 ); break;
			default: $info = 
				array(
					'estado' 	=> 'Desconhecido',
					'descricao' => 'Esse estado não consta na documentação do PagSeguro.'
				 ); break;
		}
		$info['status'] = $status;
		return $info;
	}

	// =================================================================
	// GET e SET
	// =================================================================
	 
	/**
	* @param $emailCliente string
	*/
	public function setEmailCliente($emailCliente) {
	    return $this->cliente['senderEmail'] = $emailCliente;
	}

	/**
	* @param $nomeCliente string
	*/
	public function setNomeCliente($nomeCliente) {
	    $this->cliente['senderName'] = $nomeCliente;
	}

	public function adicionarItem($id, $descricao, $valor, $quantidade) {
		$index = count($this->itens) + 1;
		$valor = number_format($valor, 2, '.', '');
		$this->itens[] = array(
			'itemId'.$index 			=> $id,
			'itemDescription'.$index 	=> $descricao,
			'itemAmount'.$index 		=> $valor,
			'itemQuantity'.$index 		=> $quantidade
		);
	}

	/**
	* @param $referencia string
	*/
	public function setReferencia($referencia) {
	    return $this->referencia = $referencia;
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

	/********** REST ******************/
	/**
	* Realiza uma requisição GET
	* @access private
	* @param $url string
	* @return array
	*/
	private function get($url, $dados = array()) {
		$dados = array_merge($this->getCredenciais(), $dados);

		$curl = curl_init($url . '?' . http_build_query($dados));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
		
		$xml = curl_exec($curl);
		curl_close($curl);
		
		if ($xml == 'Unauthorized') 
			throw new \Exception("Falha na autenticação");
		
		$xml = simplexml_load_string($xml);
		
        return $xml;

	}

	/**
	* Realiza uma requisição POST
	* @access private
	* @param $url string
	* @param $data array
	* @return array
	*/
	private function post($url, $dados = array()) {
		$dados = array_merge($this->getCredenciais(), $dados);
		$curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");  
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        @curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($dados));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$xml = curl_exec($curl);
		curl_close($curl);

		if ($xml == 'Unauthorized') 
			throw new \Exception("Falha na autenticação");

		$xml = simplexml_load_string($xml);

        return $xml;
	}
}