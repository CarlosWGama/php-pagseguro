<?php
namespace CWG\PagSeguro;

use CWG\PagSeguro\PagSeguroBase;
/**
* @package Library
* @category Pagamento Único
* @author Carlos W. Gama (carloswgama@gmail.com)
* @license MIT
* @version 3.0.0
* @since 3.0.0
* Classe de pagamento único no PagSeguro
*/
class PagSeguroCompras extends PagSeguroBase {

	//==================================================
	//                       URL
	//==================================================
	/**
	* URL para o pagamento em produção
	* @access protected
	* @var string
	*/
	protected $urlPagamento = 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=';

	/**
	* URL para o pagamento em Sandbox
	* @access protected
	* @var string
	*/
	protected $urlPagamentoSandbox = 'https://sandbox.pagseguro.uol.com.br/v2/checkout/payment.html?code=';
	
	/**
	 * URL para abrir o Lightbox do PagSeguro
	 * @access private
	 * @var string
	 */
	private $urlLightBox = 'https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.lightbox.js';
	
	/**
	 * URL para abrir o Lightbox do PagSeguro
	 * @access private
	 * @var string
	 */
	private $urlLighboxSandbox = 'https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.lightbox.js';

	//===================================================
	// 					Dados da Compra
	//===================================================
	/**
	* O nome e mail do cliente | Deve ser um nome composto
	* @access private
	* @var array
	*/
	private $cliente = [
		'senderName' 	=> '',
		'senderEmail'	=> '',
	];

	/**
	* Dados do cartão ddo cliente para  Checkout Transparente
	* @access private
	* @var array
	*/
	private $checkoutTransparente = [
		'cliente' => [
			'senderName' 	=> '',
			'senderEmail'	=> '',
			'senderCPF'		=> '',
			'senderHash'	=> '',
			'senderAreaCode'=> '',
			'senderPhone'	=> '',

			'shippingAddressRequired'	=> false
		],
		'cartao' => [
			'creditCardToken'		=> '',
			'creditCardHolderName'	=> '',
			'creditCardHolderCPF'	=> '',
			'creditCardHolderBirthDate'	=> '',
			'creditCardHolderAreaCode'	=> '',
			'creditCardHolderPhone'	=> '',
			
			'installmentQuantity' => '',
			'installmentValue'	  => '',
			'noInterestInstallmentQuantity'	=> '1',

			'billingAddressStreet'		=> '',
			'billingAddressNumber'		=> '',
			'billingAddressComplement'	=> '',
			'billingAddressDistrict'	=> '',
			'billingAddressPostalCode'	=> '',
			'billingAddressCity'		=> '',
			'billingAddressState'		=> '',
			'billingAddressCountry'		=> 'BRA'
		],
		'debito' => [
			'bankName'	=> ''
		],
		'paymentMethod'	=> ''
	];

	/**
	* Dados do endereço cliente para  Checkout Transparente
	* @access private
	* @var array
	*/
	private $endereco = [

	];

	/**
	* Dados do endereço cliente para  Checkout Transparente
	* @access private
	* @var array
	*/
	private $debito = [

	];

	/**
	* Lista de itens
	* @var array
	*/
	private $itens = array();

	/** 
	* Headers para acesso a API do gerarSolicitacaoPagSeguro
	* @access private
	* @var array
	*/
	private $headers = array(
		'Content-Type: application/x-www-form-urlencoded; charset=ISO-8859-1', 
		'Accept: application/xml;charset=ISO-8859-1'
	);
	
	/**
	 * Onde será setado o valor de Limite para Parcelamento
	 * @access private
	 * @var int
	 */
	private $parcelaLimit = '';
	

	/**
	 * Define quais formatos de pagamentos é valido no Checkout Transparente
	 * @access private
	 * @var array
	 */
	private $formasPagamentos = [
		'cartao'	=> true,
		'boleto'	=> true,
		'debito'	=> true
	];

	// ================================================================
	// API Compra PagSeguro
	// ================================================================	
	/**
	* Inicia um pedido de compra
	* @access private
	* @return code (Código da Compra)
	*/
	private function gerarCompra() {
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
		
		if (!empty($this->parcelaLimit)){

			$dados['paymentMethodGroup1'] = 'CREDIT_CARD';
			$dados['paymentMethodConfigKey1_1']	= 'MAX_INSTALLMENTS_LIMIT';
			$dados['paymentMethodConfigValue1_1'] = $this->parcelaLimit;
		}

		$response = $this->post($this->getURLAPI('v2/checkout'), $dados);

		if (isset($response->code)) {
			return $response->code;
		} else {
			throw new \Exception($response->error->message);
		}	
	}

	/**
	 * Retorna o JavaScript para abrir o Lightbox
	 * @access public
	 * @param $success (OPCIONAL) | JavaScript que será executado caso a compra seja realizada 
	 * @param $abord (OPCIONAL) | JavaScript que será executado caso o Lightbox seja fechado
	 * @return string | JavaScript para abrir o Lightbox
	 */
	public function gerarLightbox($success = '', $abord = '') {
		
		$codigoPagamento = $this->gerarCompra();

		//JavaScript do PagSeguro para abrir o Lightbox
		$javascript = '<script type="text/javascript" src="' . $this->getURLLightbox() . '"></script>';

		//Códigos que definem o que fazer quando a compra é finalizada ou abortada
		$javascript .= '<script type="text/javascript">';
		$javascript .= 'var callback = { 
			//Insira os comandos para quando o usuário finalizar o pagamento. 
			success : function(transactionCode) {
				' . $success . '
				console.log("Compra feita com sucesso, código de transação: " + transactionCode);
			},
			//Insira os comandos para quando o usuário abandonar a tela de pagamento.
			abort : function() {
				' . $abord . '
				console.log("abortado");
			}
		};';

		//Chamada do lightbox passando o código de checkout e os comandos para o callback
		$javascript .= "var isOpenLightbox = PagSeguroLightbox('$codigoPagamento', callback);";

		// Redireciona o comprador, caso o navegador não tenha suporte ao Lightbox
		$javascript .= 'if (!isOpenLightbox) location.href="' . $this->gerarURLCompra() . '";';
		$javascript .= '</script>';

		return $javascript;
	}

	/**
	* Gera todo o JavaScript necessário para o CheckoutTransparente
	* @param $urlCompletar (string) URL para completar a requisição
	* @param $jsSuccess (string) JavaScript opcional para executar ao completar a compra
	* @param $importaJquery (boolean) Importa o JQuery
	* @return string Retorna o JavaScript e HTML necessário apra checkout transparente
	*/
	public function preparaCheckoutTransparente($urlCompletar, $jsSuccess = '', $importaJquery = false) {
	
		$javascript = $this->preparaCheckout($importaJquery);
		$javascript['formas_pagamento'] = '';
		//===================== CARTÃO DE CREDITO ====================//
		if ($this->formasPagamentos['cartao']) {
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
						return new Promise((resolve, reject) => {
							PagSeguroDirectPayment.getBrand({cardBin: $('#pagseguro_cartao_numero').val(),
								success: function(response) { console.log('Bandeira: ' + response.brand.name); $('#pagseguro_cartao_bandeira').val(response.brand.name); resolve(response.brand.name)},
								error: function(response) { console.log(response); resolve('visa') },
							});
						});
					}
				</script>";

			//Parcelas de cartão
			$javascript['cartao_parcelamento'] = "
			<script type='text/javascript'>
				function PagSeguroAtualizaParcela() {
					PagSeguroBuscaBandeira().then(bandeira => {
						
						PagSeguroDirectPayment.getInstallments({
							amount: " . $this->valor . ",
							maxInstallmentNoInterest:  " . $this->checkoutTransparente['cartao']['noInterestInstallmentQuantity'] . ",
							success: function(response) {
								$('#pagseguro_cartao_parcela').html('');
								
								
								console.log(bandeira);
								response.installments[bandeira].forEach((p) => {
									//Adiciona parcelas possíveis no SELECT
									let texto = '(R$' + p.totalAmount + ') ' +  p.quantity + 'x de R$' + p.installmentAmount;
									$('#pagseguro_cartao_parcela').append(new Option(texto, p.quantity+'-'+p.installmentAmount));	
								});
							},
							error: function(response) { console.log(response); }
						});
					});
				}
				PagSeguroAtualizaParcela();
			</script>";

			//Botão para concluir a compra
			$javascript['formas_pagamento'] .= "
			<script type='text/javascript'>
				$('.pagseguro-pagar-cartao').click(() => {
					console.log('Iniciado Pagamento com Cartao');

					let valorAnterior = $('.pagseguro-pagar-cartao').html();
					$('.pagseguro-pagar-cartao').html('Aguarde');
					$('.pagseguro-pagar-cartao').attr('disabled', true);

					//Gera os conteúdos necessários
					PagSeguroBuscaHashCliente(); //Cria o Hash identificador do Cliente usado na transição
					PagSeguroBuscaBandeira();   //Através do pagseguro_cartao_numero do cartão busca a bandeira
					PagSeguroBuscaToken();      //Através dos 4 campos acima gera o Token do cartão  
					setTimeout(() => {
				
						let parcelas = $('#pagseguro_cartao_parcela').val().split('-');
						const data = {
							hash:  $('#pagseguro_cliente_hash').val(),
							token: $('#pagseguro_cartao_token').val(),
							metodo: 'creditCard',
							parcelas: parcelas[0],
							parcelas_valor: parcelas[1]
						};
						console.log(data);
						$.post('" . $urlCompletar . "', data, function(response) {
							try { response = JSON.parse(response);} catch(e) {}
							" . $jsSuccess . "
							if (!response.success)
								alert(response.message)

							$('.pagseguro-pagar-cartao').html(valorAnterior);
							$('.pagseguro-pagar-cartao').attr('disabled', false);
						});
					}, 3000);
				});
			</script>";
		}

		//===================== DEBITO ====================//
		if ($this->formasPagamentos['debito']) {

			//Debito, seleciona 
			$javascript['debito_selecionar_banco'] = "
				<input type='hidden' id='pagseguro_debito_banco' value='bancodobrasil'  />
				<script type='text/javascript'>
					function selecionaBanco(banco) {
						if (['bradesco', 'itau', 'bancodobrasil', 'banrisul'].includes(banco))
							$('#pagseguro_debito_banco').val(banco);
					}
				</script>	
			";

			//Botão para concluir a compra
			$javascript['formas_pagamento'] .= "
			<script type='text/javascript'>
				$('.pagseguro-pagar-debito').click(() => {
					console.log('Iniciado Pagamento com Debito');

					let valorAnterior = $('.pagseguro-pagar-debito').html();
					$('.pagseguro-pagar-debito').html('Aguarde');
					$('.pagseguro-pagar-debito').attr('disabled', true);

					//Gera os conteúdos necessários
					PagSeguroBuscaHashCliente(); //Cria o Hash identificador do Cliente usado na transição
					setTimeout(() => {
				
						const data = {
							hash:  $('#pagseguro_cliente_hash').val(),
							banco: $('#pagseguro_debito_banco').val(),
							metodo: 'eft'
						};

						console.log(data);

						$.post('" . $urlCompletar . "', data, function(response) {
							try { response = JSON.parse(response);} catch(e) {}
							
							" . $jsSuccess . "
							if (response.success)
								window.open(response.url, '_blank');
							else
								alert(response.message)

							$('.pagseguro-pagar-debito').html(valorAnterior);
							$('.pagseguro-pagar-debito').attr('disabled', false);
						});
					}, 3000);
				});
			</script>";
		}

		//===================== BOLETO ====================//
		if ($this->formasPagamentos['boleto']) {
			//Botão para concluir a compra
			$javascript['formas_pagamento'] .= "
			<script type='text/javascript'>
				$('.pagseguro-pagar-boleto').click(() => {

					let valorAnterior = $('.pagseguro-pagar-boleto').html();
					$('.pagseguro-pagar-boleto').html('Aguarde');
					$('.pagseguro-pagar-boleto').attr('disabled', true);

					console.log('Iniciado Pagamento com Boleto');
					//Gera os conteúdos necessários
					PagSeguroBuscaHashCliente(); //Cria o Hash identificador do Cliente usado na transição
					setTimeout(() => {
				
						const data = {
							hash:  $('#pagseguro_cliente_hash').val(),
							metodo: 'boleto'
						};
						console.log(data);

						$.post('" . $urlCompletar . "', data, function(response) {
							try { response = JSON.parse(response);} catch(e) {}
							
							" . $jsSuccess . "
							if (response.success)
								window.open(response.url, '_blank');
							else
								alert(response.message)

							$('.pagseguro-pagar-boleto').html(valorAnterior);
							$('.pagseguro-pagar-boleto').attr('disabled', false);
						});
					}, 3000);
				});
			</script>";

		}

		$javascript['completo'] = implode(' ', $javascript);
		return $javascript;
	}

	public function pagarCheckoutTransparente() {
		$dados = array();
		//Dados do cliente
		$dados = array_merge($this->checkoutTransparente['cliente'], $dados);
		
		//Itens
		foreach ($this->itens as $itens) {
			foreach ($itens as $key => $value)
				$dados[$key] = $value;
		}
		
		//Links
		if (isset($this->notificationURL))
			$dados['notificationURL'] = $this->notificationURL;


		//Dados da compra
		$dados['reference']		= $this->referencia;
		$dados['currency'] 		= 'BRL';

		//Checkout Transparente
		$dados['paymentMode'] = 'default';
		$dados['paymentMethod'] = $this->checkoutTransparente['paymentMethod'];
		if ($dados['paymentMethod'] == 'creditCard')
			$dados = array_merge($this->checkoutTransparente['cartao'], $dados);
		else if ($dados['paymentMethod'] == 'eft') 
			$dados = array_merge($this->checkoutTransparente['debito'], $dados);

		$response = $this->post($this->getURLAPI('v2/transactions'), $dados);
	

		$metodo = $dados['paymentMethod'];
		if ($dados['paymentMethod'] == 'creditCard') $metodo = 'cartao';
		if ($dados['paymentMethod'] == 'eft') $metodo = 'debito';

		if (isset($response->code)) {
			if ($dados['paymentMethod'] == 'creditCard')
				return json_encode(['success' => true, 'status' => (boolean)$response->status, 'method' => $metodo]);
			else
				return json_encode(['success' => true, 'url' => (string)$response->paymentLink, 'method' => $metodo]); //link para o pagamento
		} else {
				return json_encode(['success' => false, 'message' => (string)$response->error->message, 'method' => $metodo]); //link para o pagamento
		}	
	}
	
	/**
	* Inicia um pedido de compra e retorna a URL para a compra
	* @access public
	* @return array (url para a compra e código da compra)
	*/
	public function gerarURLCompra() {
		return $this->getURLPagamento() .$this->gerarCompra();
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
	 * Retorna a URL do Lightbox de acordo com o ambiente 
	 * @access private
	 * @return url 
	 */
	private function getURLLightbox() {
		return (!$this->isSandbox ? $this->urlLightBox : $this->urlLighboxSandbox);
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

	
	/**
	 * Habilita ou desabilita Boleto
	 * @access public
	 * @param $habilita boolean
	 * @return O Próprio objeto
	 */
	public function habilitaBoleto($habilita) {
		$this->formasPagamentos['boleto'] = $habilita;
		return $this;
	}
	
	/**
	 * Habilita ou desabilita Debito
	 * @access public
	 * @param $habilita boolean
	 * @return O Próprio objeto
	 */
	public function habilitaDebito($habilita) {
		$this->formasPagamentos['debito'] = $habilita;
		return $this;
	}
	
	/**
	 * Habilita ou desabilita Cartão
	 * @access public
	 * @param $habilita boolean
	 * @return O Próprio objeto
	 */
	public function habilitaCartao($habilita) {
		$this->formasPagamentos['cartao'] = $habilita;
		return $this;
	}


	// =================================================================
	// GET e SET
	// =================================================================
	 
	/**
	* @param $emailCliente string
	*/
	public function setEmailCliente($emailCliente) {
		$this->cliente['senderEmail'] = $emailCliente;
		$this->checkoutTransparente['cliente']['senderEmail'] = $emailCliente;
		return $this;
	}

	/**
	* @param $nomeCliente string
	*/
	public function setNomeCliente($nomeCliente) {
		$this->cliente['senderName'] = $nomeCliente;
		$this->checkoutTransparente['cliente']['senderName'] = $nomeCliente;
		$this->checkoutTransparente['cartao']['creditCardHolderName'] = $nomeCliente;
		return $this;
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

		$this->valor += ($valor*$quantidade);
		return $this;
	}

		
	/**
	 * @param $parcelaLimit int
	 */
	public function setParcelaLimit($parcelaLimit) {
		$this->parcelaLimit = $parcelaLimit;
		return $this;
	}

	/* ---- SET exclusivos do Checkout Transparente ----- */
	/**
	* @param $hash string
	*/
	public function setHashCliente($hash) {
		$this->checkoutTransparente['cliente']['senderHash'] = $hash;
		return $this;
	}

	
	/** Seta o CPF do Cliente **/
	public function setCPF($cpf) {
		$this->checkoutTransparente['cliente']['senderCPF'] = $cpf;
		$this->checkoutTransparente['cartao']['creditCardHolderCPF'] = $cpf;

		return $this;
	}

	/** Adiciona o telefone do Cliente
	* @param $ddd int
	* @param $numero int
	*/
	public function setTelefone($ddd, $numero) {
		$this->checkoutTransparente['cliente']['senderAreaCode'] = $ddd;
		$this->checkoutTransparente['cliente']['senderPhone'] = $numero;

		$this->checkoutTransparente['cartao']['creditCardHolderAreaCode'] = $ddd;
		$this->checkoutTransparente['cartao']['creditCardHolderPhone'] = $numero;

		return $this;
	}

	/**
	* Seta o dia de nascimento do cliente
	* @param $ano (dd/MM/YYYY)
	*/
	public function setNascimentoCliente($ano) {
		$this->checkoutTransparente['cartao']['creditCardHolderBirthDate'] = $ano;
		return $this;
	}
	
	/**
	 *  Adiciona o Endereço do Cliente nas Compras com Checkout via cartão
	 */
	public function setEnderecoCliente($rua, $numero, $complemento, $bairro, $cidade, $estado, $cep) {
		$this->checkoutTransparente['cartao']['billingAddressStreet'] = $rua;
		$this->checkoutTransparente['cartao']['billingAddressNumber'] = $numero;
		$this->checkoutTransparente['cartao']['billingAddressComplement'] = $complemento;
		$this->checkoutTransparente['cartao']['billingAddressDistrict'] = $bairro;
		$this->checkoutTransparente['cartao']['billingAddressCity'] = $cidade;
		$this->checkoutTransparente['cartao']['billingAddressState'] = $estado;
		$this->checkoutTransparente['cartao']['billingAddressPostalCode'] = $cep;

		return $this;
	}

	/**
	 * Informa a quantidade de parcelas sem juros
	 */
	public function setParcelasSemJuros($qt) {
		$this->checkoutTransparente['cartao']['noInterestInstallmentQuantity'] = $qt;
	}

	/**
	 * Cadastra o valor da parcela
	 * @param $quantidade int 
	 * @param $valorParcela number valor das parcelas
	 */
	public function setParcelas($quantidade, $valorParcela, $parcelasSemJuros = null) {
		$this->checkoutTransparente['cartao']['installmentQuantity'] = $quantidade;
		$this->checkoutTransparente['cartao']['installmentValue'] = number_format($valorParcela, 2, '.', '');
		if ($parcelasSemJuros != null)
			$this->checkoutTransparente['cartao']['noInterestInstallmentQuantity'] = $parcelasSemJuros;
		return $this;
	}

	/**
	 * Cadastra o token do Cartão
	 * @param $token string Token do cartão
	 */
	public function setTokenCartao($token) {
		$this->checkoutTransparente['cartao']['creditCardToken'] = $token;
		return $this;
	}

	/**
	 * Informa o banco que será feito o pagamento
	 * @param $banco string nome do banco (bancodobrasil|banrisul|bradesco|itau)
	 */
	public function setBancoDebito($banco) {
		$this->checkoutTransparente['debito']['bankName'] = $banco;
		return $this;
	}

	/**
	 * Informa o tipo de pagamento que está sendo realizado
	 * @param $metodo string (creditCard|boleto|eft)
	 */
	public function setMetodoPagamento($metodo) {
		$this->checkoutTransparente['paymentMethod'] = $metodo;
	}

	/********** REST ******************/
	/**
	* Realiza uma requisição GET
	* @access private
	* @param $url string
	* @return array
	*/
	private function get($url, $dados = array()) {
		$dados = array_merge($this->getCredenciais(false), $dados);

		$curl = curl_init($url . '?' . http_build_query($dados));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
		@curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
		
		$xml = curl_exec($curl);
		curl_close($curl);
		
		if ($xml == 'Unauthorized') 
			throw new \Exception("Falha na autenticação");
		if ($xml == 'Not Found')
			throw new \Exception("Não encontrado");
		
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
		$dados = array_merge($this->getCredenciais(false), $dados);
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
		if ($xml == 'Not Found')
			throw new \Exception("Não encontrado");

		$xml = simplexml_load_string($xml);

        return $xml;
	}
}
