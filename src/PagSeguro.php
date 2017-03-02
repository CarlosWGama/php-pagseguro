<?php

namespace CWG\PagSeguro;

/**
* @package Library
* @category Pagamento
* @author Carlos W. Gama (carloswgama@gmail.com)
* @license MIT
* @version 1.0.0
* Classe de pagamento de Recursivo/Assinaturas no PagSeguro
*/
class PagSeguro {
	
	//===================================================
	// 					URL
	//===================================================
	/**
	* URL para a API em produção
	* @access private
	* @var string
	*/
	private $urlAPI = 'https://ws.pagseguro.uol.com.br/v2/pre-approvals/';

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
	private $urlAPISandbox = 'https://ws.sandbox.pagseguro.uol.com.br/v2/pre-approvals/';

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
	* O nome do cliente | Deve ser um nome composto
	* @access private
	* @var string
	*/
	private $nomeCliente = '';

	/**
	* O email do cliente 
	* @access private
	* @var string
	*/
	private $emailCliente = '';

	/**
	* Um ID qualquer para identificar qual é a compra no sistema 
	* @access private
	* @var string
	*/
	private $referencia = '';

	/**
	* Empresa responsável
	* @access private
	* @var string
	*/
	private $razao = ' CWG ';

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

	/**
	* Valor máximo que pode ser cobrado na soma do valor da assinatura
	* @access private
	* @var float
	*/
	private $valorMaximo = 0.00;

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
	* Inicia um pedido de compra
	* @access public
	* @return string (url para a compra)
	*/
	public function gerarSolicitacaoPagSeguro() {

		$dados = $this->getCredenciais();
		

		//Dados do cliente
		$dados['senderEmail'] 					= $this->getEmailCliente();
		$dados['senderName'] 					= $this->getNomeCliente();

		//Dados da assinatura
		$dados['preApprovalCharge'] 			= 'auto';
		$dados['preApprovalName']				= $this->getReferencia();
		$dados['preApprovalDetails']			= $this->getRazao() . $this->getReferencia();
		$dados['preApprovalAmountPerPayment']	= $this->getValor();
		$dados['preApprovalPeriod']				= $this->getPeriodicidade();
		$dados['preApprovalFinalDate']			= date('Y-m-d\TH:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), (date('Y')+2))); //2 anos
		$dados['preApprovalMaxTotalAmount']		= $this->getValorMaximo();
		$dados['redirectURL']					= $this->getRedirectURL();
		$dados['notificationURL']				= $this->getNotificationURL();
		$dados['reference']						= $this->getReferencia();

		$dados = http_build_query($dados);

		$curl = curl_init($this->getURLAPI() . 'request');
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $dados);
		$xml = curl_exec($curl);
		curl_close($curl);

		//Problema Token do vendedor
		if ($xml == 'Unauthorized') 
			throw new \Exception("Token inválido");
		

		$xml = simplexml_load_string($xml);
		
		//Erros
		if ($xml[0]->error) {
			$erros = array();

			foreach ($xml as $erro) 
				$erros[] = $erro->message;

			throw new \Exception(implode(", ", $erros));
		}

		if (isset($xml->code)) 
			return $this->getUrlPagamento() . $xml->code;
		
	}

	/**
	* Cancela a assinatura
	* @access public
	* @param $codePagSeguro string (Código fornecido pelo pagseguro para uma compra)
	* @return bool
	*/
	public function suspenderAssinatura($codePagSeguro) {
		$dados = $this->getCredenciais();

		$dados = '?' . http_build_query($dados);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->getURLAPI() . 'cancel/' . $codePagSeguro . $dados);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		
		$xml = curl_exec($curl);
		curl_close($curl);		

		//Problema Token do vendedor
		if ($xml == 'Unauthorized') {
			throw new \Exception("Token inválido");
		}

		$xml = simplexml_load_string($xml);
		
		//Erros
		if ($xml[0]->error) {
			$erros = array();

			foreach ($xml as $erro) 
				$erros[] = $erro->message;

			throw new \Exception(implode(", ", $erros));
		}

		return ($xml->status == 'OK'? true : false);	
	}

	/**
	* Consulta informações de uma compra
	* @access public
	* @param $codePagSeguro string (Código fornecido pelo pagseguro para uma compra)
	* @return array (Com os dados da compra)
	*/
	public function consultarAssinatura($codePagSeguro) {
		$dados = $this->getCredenciais();

		$dados = '?' . http_build_query($dados);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->getURLAPI() . $codePagSeguro . $dados);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		$xml = curl_exec($curl);
		curl_close($curl);

		//Problema Token do vendedor
		if ($xml == 'Unauthorized') {
			throw new \Exception("Token inválido");
		}

		$xml = simplexml_load_string($xml);
		
		//Erros
		if ($xml[0]->error) {
			$erros = array();

			foreach ($xml as $erro) 
				$erros[] = $erro->message;

			throw new \Exception(implode(", ", $erros));
		}

		if ($xml->code == $codePagSeguro)
			return json_decode(json_encode((array) $xml), true);;
	}
	
	/**
	* Retornar compras realizadas num intervalo de data
	* @access public
	* @param $dataInicial string (Padrão 'Y-m-d H:i')
	* @param $dataFinal string (Padrão'Y-m-d H:i'. Não pode ser maior que a hora atual)
	* @param $maxPageResults int (Quando itens são retornados por página da busca)
	* @param $page int (Página da busca)
	* @return array (Com os dados da compra)
	*/
	public function consultarAssinaturaPeriodo($dataInicial, $dataFinal, $maxPageResults = 100, $page = 1) {
		$dados = $this->getCredenciais();

		$dados['initialDate'] = date('Y-m-d\TH:i', strtotime($dataInicial));
		$dados['finalDate'] = date('Y-m-d\TH:i', strtotime($dataFinal));

		$dados['maxPageResults'] = $maxPageResults;
		$dados['page'] = $page;
		$dados = '?' . http_build_query($dados);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->getURLAPI() . $dados);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		$xml = curl_exec($curl);
		curl_close($curl);

		//Problema Token do vendedor
		if ($xml == 'Unauthorized') {
			throw new \Exception("Token inválido");
		}

		$xml = simplexml_load_string($xml);
		
		//Erros
		if ($xml[0]->error) {
			$erros = array();

			foreach ($xml as $erro) 
				$erros[] = $erro->message;

			throw new \Exception(implode(", ", $erros));
		}

		if (count($xml->totalPages)) {
			return $xml;
		}
	}
	// =================================================================
	// Util
	// =================================================================
	/**
	* Verifica se o periodo de renovação da cobrança é válido
	* @access public
	* @param $periodo int
	* @return bool
	*/
	public function validarPeriodo($periodo) {
		return in_array($periodo, array(0, 1, 2, 3, 6, 12));
	}

	/**
	* Busca o Código do PagSeguro de uma compra especifica por sua referência
	* @access public
	* @param $vendaID string (referencia)
	* @param $dataInicial string (Intervalo da compra)
	* @param $dataFinal string (Intervalo da compra)
	* @return string (Código do PagSeguro)
	*/
	public function getPreApprovalCodeByVenda($vendaID, $dataInicial = '', $dataFinal = '') {
		try {
			if (empty($dataInicial)) $dataInicial = date('Y-m-d 00:00');
			if (empty($dataInicial)) $dataFinal = date('Y-m-d H:i');

			$xml = $this->consultarAssinaturaPeriodo($dataInicial, $dataFinal);
			$xml = json_decode(json_encode((array) $xml), true);
			
			if (empty($xml['preApprovals']))
				return false; //Não há nada

			foreach ($xml['preApprovals']['preApproval'] as $preApproval) {
				if ($vendaID == $preApproval['name']) {
					return $preApproval['code'];
				}
			}	
			//Não achou 
			return false;

		} catch (\Exception $e) {
			return false;
		}
	}

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
	* @return string
	*/
	public function getNomeCliente() {
	    return $this->nomeCliente;
	}
	 
	/**
	* @param $nomeCliente string
	*/
	public function setNomeCliente($nomeCliente) {
	    return $this->nomeCliente = $nomeCliente;
	}
	
	/**
	* @return string
	*/
	public function getEmailCliente() {
	    return $this->emailCliente;
	}
	 
	/**
	* @param $emailCliente string
	*/
	public function setEmailCliente($emailCliente) {
	    return $this->emailCliente = $emailCliente;
	}
	
	/**
	* @return string
	*/
	public function getReferencia() {
	    return $this->referencia;
	}
	 
	/**
	* @param $referencia string
	*/
	public function setReferencia($referencia) {
	    return $this->referencia = $referencia;
	}
	
	/**
	* @return string
	*/
	public function getRazao() {
	    return $this->razao;
	}
	 
	/**
	* @param $razao string
	*/
	public function setRazao($razao) {
	    return $this->razao = $razao;
	}
	
	/**
	* @return float
	*/
	public function getValor() {
	    return $this->valor;
	}
	 
	/**
	* @param $valor float
	*/
	public function setValor($valor) {
	    return $this->valor = $valor;
	}
	
	/**
	* @return string
	*/
	public function getPeriodicidade() {
	    return $this->periodicidade;
	}

	/**
	* @param $periodicidade int | string('WEEKLY', 'MONTHLY', 'BIMONTHLY', 'TRIMONTHLY', 'SEMIANNUALLY', 'YEARLY')
	*/
	public function setPeriodicidade($periodicidade) {

		if (is_int($periodicidade)) {
			switch($periodicidade) {
				case 0:
					$this->periodicidade = "WEEKLY";
					break;
				case 1:
					$this->periodicidade = 'MONTHLY';
					break;
				case 2:
					$this->periodicidade = 'BIMONTHLY';
					break;
				case 3:
					$this->periodicidade = 'TRIMONTHLY';
					break;
				case 6:
					$this->periodicidade = 'SEMIANNUALLY';
					break;
				case 12:
					$this->periodicidade = 'YEARLY';
					break;
				default:
					$this->periodicidade = '-'; //erro
					break;
			}
		} else 
			$this->periodicidade = $periodicidade;
		
		//Tratamento
		if (!in_array($this->periodicidade, array('WEEKLY', 'MONTHLY', 'BIMONTHLY', 'TRIMONTHLY', 'SEMIANNUALLY', 'YEARLY')))
			$this->periodicidade = '-'; //Erro

	    return $this->periodicidade;
	}
	
	/**
	* @return float
	*/
	public function getValorMaximo() {
		if ($this->periodicidade == 0.00) {
			switch ($this->periodicidade) {
				case 'MONTHLY':
					$this->valorMaximo = $this->valor * 24;
					break;
				case 'BIMONTHLY':
					$this->valorMaximo = $this->valor * 12;
					break;
				case 'TRIMONTHLY':
					$this->valorMaximo = $this->valor * 8;
					break;
				case 'SEMIANNUALLY':
					$this->valorMaximo = $this->valor * 4;
					break;
				case 'YEARLY':
					$this->valorMaximo = $this->valor * 2;
					break;
			}	
		}
		
	    return number_format($this->valorMaximo, 2, '.', '');
	}
	 
	/**
	* @param $valorMaximo float
	*/
	public function setValorMaximo($valorMaximo) {
	    return $this->valorMaximo = $valorMaximo;
	}
	
	/**
	* @return string
	*/
	public function getRedirectURL() {
	    return $this->redirectURL;
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
	public function getNotificationURL() {
		return $this->notificationURL;
	}

	/**
	* @param $notificationURL string
	*/
	public function setNotificationURL($notificationURL) {
	    $this->notificationURL = $notificationURL;
	}
	
	/**
	* @return string
	*/
	public function getPreApprovalCode() {
	    return $this->preApprovalCode;
	}
	
	/**
	* @param $preApprovalCode string
	*/
	public function setPreApprovalCode($preApprovalCode) {
	    return $this->preApprovalCode = $preApprovalCode;
	}

}