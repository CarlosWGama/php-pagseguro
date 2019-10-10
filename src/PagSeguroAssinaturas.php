<?php
namespace CWG\PagSeguro;

use CWG\PagSeguro\PagSeguroBase;

/**
* @package Library
* @category Assinatura
* @author Carlos W. Gama (carloswgama@gmail.com)
* @license MIT
* @version 3.2.0
* @since 3.0.0
* Classe de pagamento de Recursivo/Assinaturas no PagSeguro
*/
class PagSeguroAssinaturas extends PagSeguroBase {

	//==================================================
	//                     URL
	//==================================================
	/**
	* URL para o pagamento em produção
	* @access protected
	* @var string
	*/
	protected $urlPagamento = 'https://pagseguro.uol.com.br/v2/pre-approvals/request.html?code=';

	/**
	* URL para o pagamento em Sandbox
	* @access protected
	* @var string
	*/
	protected $urlPagamentoSandbox = 'https://sandbox.pagseguro.uol.com.br/v2/pre-approvals/request.html?code=';

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
	* Descricao da compra
	* @access private
	* @var string
	*/
	private $descricao = ' CWG ';

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
	* Período de teste, em dias. A recorrência mantém o status de iniciada durante o período de testes, de modo que a primeira cobrança só ocorrerá após esse período, permitindo que a recorrência se torne ativa. No caso de pagamento pré-pago, a cobrança se dá imediatamente após o fim do período de testes; no caso de pagamento pós-pago, a cobrança ocorre após o período de cobrança somado ao período de testes.
	* @var integer
	*/
	private $trial = '';

	/**
	* Endereço de IP de origem da adesão ao plano, relacionado ao assinante.
	* @var string
	* Formato: 4 números, de 0 a 255, separados por ponto (Ex: 127.0.0.1)
	*/
	private $ip = '';

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

	// ================================================================
	// API Assinatura PagSeguro
	// ================================================================
	

	/**
	* Gera todo o JavaScript necessário
	*/
	public function preparaCheckoutTransparente($importaJquery = false) {
		//Recupera o JavaScript padrão para assinatura e compra 
		$javascript = $this->preparaCheckout($importaJquery);

		//Obter token do Cartão
		$javascript['cartao_token'] = "
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

		//Obter bandeira
		$javascript['cartao_bandeira'] = "
			<input type='hidden' id='pagseguro_cartao_bandeira' />
			<script type='text/javascript'>
				function PagSeguroBuscaBandeira() {
					PagSeguroDirectPayment.getBrand({cardBin: $('#pagseguro_cartao_numero').val(),
						success: function(response) { console.log('Bandeira: ' + response.brand.name); $('#pagseguro_cartao_bandeira').val(response.brand.name)},
						error: function(response) { console.log(response); },
					});
				}
			</script>";

		$javascript['completo'] = implode(' ', $javascript);
		return $javascript;
	}

	/**
	* Criar um novo plano
	*/
	public function criarPlano() {

		//Dados da assinatura
		$dados['reference']						  = $this->referencia;
		$dados['preApproval']['charge'] 		  = 'auto';
		$dados['preApproval']['name']			  = $this->referencia;
		$dados['preApproval']['details']		  = $this->descricao;
		$dados['preApproval']['amountPerPayment'] = $this->valor;
		$dados['preApproval']['period']			  = $this->periodicidade;
		$dados['preApproval']['expiration']		  = $this->expiracao;

		//Opcionais
		if (!empty($this->trial))
			$dados['preApproval']['trialPeriodDuration'] = $this->trial;

		if (!empty($this->URLCancelamento))
			$dados['preApproval']['cancelURL'] = $this->URLCancelamento;

		if (!empty($this->redirectURL))
			$dados['redirectURL'] = $this->redirectURL;

		// if (!empty($this->notificationURL))
			// $dados['notificationURL']	= $this->notificationURL;


		if ($this->maximoUsuarios > 0)
			$dados['maxUses'] = $this->maximoUsuarios;

		$response = $this->post($this->getURLAPI('pre-approvals/request'), $dados);

		if ($response['http_code'] == 200) {
			return $response['body']['code'];
		} else {
			throw new \Exception(current($response['body']['errors']));
		}
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

		$response = $this->post($this->getURLAPI('pre-approvals/'), $dados);

		if ($response['http_code'] == 200) {
			return $response['body']['code'];
		} else {
			throw new \Exception(current($response['body']['errors']), key($response['body']['errors']));
		}
	}

	/**
	* Realiza assinatura do plano pelo ambiente checkout padrão
	* @access public
	* @param $planoCode | Código do plano no qual o usuário deveria ser redrecionado para o checkout
	*/
	public function assinarPlanoCheckout($planoCode) {
		return $this->getURLPagamento() . $planoCode;
	}

	/** 
	 * Realiza uma consulta a notificação
	 * @access public
	 * @param $codePagSeguro | Codigo da notificação enviada pelo pagaseguro
	 **/
	public function consultarNotificacao($codePagSeguro) {
		$response = $this->get($this->getURLAPI('pre-approvals/notifications/') . $codePagSeguro);

		if ($response['http_code'] == 200) {
			return $response['body'];
		} else {
			throw new \Exception(current($response['body']['errors']));
		}
	}

	/** 
	 * Consulta uma assinatura 
	 * @access public
	 * @param $codePagSeguro | Codigo unico da assinatura do cliente no plano
	 **/
	public function consultaAssinatura($codePagSeguro) {
		$response = $this->get($this->getURLAPI('pre-approvals/') . $codePagSeguro);

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
		$response = $this->put($this->getURLAPI('pre-approvals/' . $codePagSeguro . '/cancel'));

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
		$response = $this->put($this->getURLAPI('pre-approvals/' . $codePagSeguro . '/status'), $dados);

		if ($response['http_code'] == 204) {
			return true;
		} else {
			throw new \Exception(current($response['body']['errors']));
		}
	}

	// =================================================================
	// GET e SET
	// =================================================================

	/**
	* @param $emailCliente string
	*/
	public function setEmailCliente($emailCliente) {
		$this->cliente['email'] = $emailCliente;
		return $this;
	}

	/**
	* @param $razao string
	*/
	public function setDescricao($descricao) {
		$this->descricao = $descricao;
		return $this;
	}

	/**
	* @param $valor float
	*/
	public function setValor($valor) {
		$this->valor = number_format($valor, 2, '.', '');
		return $this;
	}

	/**
	* @param $trial integer | max 100
	*/
	public function setTrial($trial) {
		$this->trial = intval($trial);
		return $this;
	}

	/**
	* @param $periodicidade int | string('WEEKLY', 'MONTHLY', 'BIMONTHLY', 'TRIMONTHLY', 'SEMIANNUALLY', 'YEARLY')
	*/
	public function setPeriodicidade($periodicidade) {
		$this->periodicidade = $periodicidade;

		//Tratamento
		if (!in_array($this->periodicidade, array('WEEKLY', 'MONTHLY', 'BIMONTHLY', 'TRIMONTHLY', 'SEMIANNUALLY', 'YEARLY')))
			$this->periodicidade = '-'; //Erro

		$this->periodicidade;
		return $this;
	}

	/**
	* @param $preApprovalCode string
	*/
	public function setPreApprovalCode($preApprovalCode) {
		$this->preApprovalCode = $preApprovalCode;
		return $this;
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
		return $this;
	}

	/**
	* Seta a url para onde o usuário é enviado para cancelar a assinatura
	* @param $url string
	*/
	public function setURLCancelamento($url) {
		$this->URLCancelamento = $url;
		return $this;
	}

	/**
	* Informa o máximo de usuários a usar o plano
	*/
	public function setMaximoUsuariosNoPlano($valor) {
		$this->maximoUsuarios = intval($valor);
		return $this;
	}


	/**
	* @param $preApprovalCode string
	*/
	public function setPlanoCode($planoCode) {
		$this->planoCode = $planoCode;
		return $this;
	}

	/**
	* @param $hash string
	*/
	public function setHashCliente($hash) {
		$this->cliente['hash'] = $hash;
		return $this;
	}

	/**
	* @param $ip string
	*/
	public function setIPCliente($ip) {
		$this->cliente['ip'] = $ip;
		return $this;
	}

	/**
	* @param $nomeCliente string
	*/
	public function setNomeCliente($nomeCliente) {
	    $this->cliente['name'] = $nomeCliente;
		$this->formaPagamento['creditCard']['holder']['name'] = $nomeCliente;
		return $this;
	}

	/**
	* Seta o dia de nascimento do cliente
	* @param $ano (dd/MM/YYYY)
	*/
	public function setNascimentoCliente($ano) {
		$this->formaPagamento['creditCard']['holder']['birthDate'] = $ano;
		return $this;
	}


	/** Seta o CPF/CNPJ do Cliente **/
	public function setCPF($numero) {
		$this->cliente['documents'][0]['value'] = $numero;
		$this->formaPagamento['creditCard']['holder']['documents'][0]['value'] = $numero;
		return $this;
	}

	/** Seta o CPF/CNPJ do Cliente 
	 * @since 3.2.0
	 **/
	public function setCNPJ($numero) {
		return $this->setCPF($numero);
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
		return $this;
	}

	/** Seta o token do Cartão **/
	public function setTokenCartao($token) {
		$this->formaPagamento['creditCard']['token'] = $token;
		return $this;
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
		return $this;
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