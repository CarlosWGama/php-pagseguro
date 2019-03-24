<?php

namespace CWG\PagSeguro;

/**
* @package Library
* @author Carlos W. Gama (carloswgama@gmail.com)
* @license MIT
* @version 3.0.0
* @since 3.0.0
* Classe com recursos comuns do PagSeguro
*/

abstract class PagSeguroBase {

    //===================================================
	// 					URL
	//===================================================
	/**
	* URL para a API em produção
	* @access protected
	* @var string
	*/
	protected $urlAPI = 'https://ws.pagseguro.uol.com.br/';

	/**
	* URL para o pagamento em produção (Definido nas classes filhas)
	* @access protected
	* @var string
	*/
	protected $urlPagamento = '';

	/**
	* URL para a API em Sandbox
	* @access protected
	* @var string
	*/
	protected $urlAPISandbox = 'https://ws.sandbox.pagseguro.uol.com.br/';

	/**
	* URL para o pagamento em Sandbox (Definido nas classes filhas)
	* @access protected
	* @var string
	*/
	protected $urlPagamentoSandbox = '';
	
	//===================================================
	// 					Dados do Pedido
	//===================================================
	/**
	* Valor cobrado
	* @access protected
	* @var float
	*/
	protected $valor = 0.00;

	/**
	* Um ID qualquer para identificar qual é a compra no sistema 
	* @access protected
	* @var string
	*/
	protected $referencia = '';

	/**
	* Link para onde a pessoa será redicionada após concluir a assinatura no Pagseguro
	* @access protected
	* @var string (url)
	*/
	protected $redirectURL = '';

	/**
	* Link para onde será enviada as notificações a cada alteração na compra
	* @access protected
	* @var string (url)
	*/
	protected $notificationURL = '';

	//===================================================
	// 					Credencias
	//===================================================

	/**
	* Email do vendedor do PagSeguro
	* @access protected
	* @var string
	*/
	protected $email;

	/**
	* token do vendedor do PagSeguro
	* @access protected
	* @var string
	*/
	protected $token;

	/**
	* Verifica se é Sanbox ou em Produção
	* @access protected
	* @var bool
	*/
	protected $isSandbox = false;

	
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
		$url = $this->getURLAPI('v2/sessions/' . $this->getCredenciais());
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
	* Gera o JavaScript básico para Assinatura e Checkout Transparente
	*/
	protected function preparaCheckout($importaJquery = false) {
		$sessionID = $this->iniciaSessao();

		$javascript = array();

		//Jquery
		if ($importaJquery) 
			$javascript['jquery'] = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>';

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
					PagSeguroDirectPayment.onSenderHashReady(function(response){
						if(response.status == 'error') {
							console.log(response.message);
							return false;
						}
						$('#pagseguro_cliente_hash').val(response.senderHash); //Hash estará disponível nesta variável.
						console.log('Hash Cliente: ' + $('#pagseguro_cliente_hash').val());
					});		
				}
			</script> \n
		";

		return $javascript;
	}

	// =================================================================
	// Util
	// =================================================================
	/**
	* Busca a URL da API de acordo com a opção Sandbox
	* @access protected
	* @return string url
	*/
	protected function getURLAPI($url = '') {
		return ($this->isSandbox ? $this->urlAPISandbox : $this->urlAPI) . $url;
	}

	/**
	* Formata a credêncial do pagseguro
	* @access protected
	* @param $httpBuildQuery | Retorna a credencial como query (TRUE) ou vetor (FALSE)
	* @return string | array(email, token)
	*/
	protected function getCredenciais($httpBuildQuery = true) {
		$dados['email'] = $this->email;
		$dados['token'] = $this->token;
		return ($httpBuildQuery ? '?' . http_build_query($dados) : $dados);
	}

	/**
	* Busca a URL de Pagamento de acordo com a opção Sandbox
	* @access protected
	* @return string url
	*/
	protected function getURLPagamento() {
		return ($this->isSandbox ? $this->urlPagamentoSandbox : $this->urlPagamento);
	}

	// =================================================================
	// GET e SET
	// =================================================================

	/**
	* @param $redirectURL string
	*/
	public function setRedirectURL($redirectURL) {
		$this->redirectURL = $redirectURL;
		return $this;
	}

	/**
	* @param $referencia string
	*/
	public function setReferencia($referencia) {
		$this->referencia = $referencia;
		return $this;
	}

	/**
	* @return string
	*/
	public function setNotificationURL($url) {
		$this->notificationURL = $url;
		return $this;
	}
}