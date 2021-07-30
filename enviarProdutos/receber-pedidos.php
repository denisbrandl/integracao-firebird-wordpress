<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require 'vendor/autoload.php';
use IntegracaoFirebirdWordpress\classes\TSD;
use IntegracaoFirebirdWordpress\classes\IntegracaoWP;
use IntegracaoFirebirdWordpress\classes\Utils;


$objIntegracaoWP		= new IntegracaoWP;
$objTSD					= new TSD;
$objUtils				= new Utils;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$arrUltimaExecucao = $objIntegracaoWP->consultaConfiguracao(
[
	'config_value' => 'ultima_execucao'
]
);

$arrUltimaExecucao['CONFIG_VALUE'] = date('Y-m-d', strtotime($arrUltimaExecucao['CONFIG_VALUE']));

$endereco_wordpress = $_ENV['ENDERECO_WORDPRESS'] . 'integracao/webservice/rest.php?acao=importa-pedido&dt_filtro='.urlencode($arrUltimaExecucao['CONFIG_VALUE']);

$retorno = $objUtils->callCurl(
	$endereco_wordpress,
	[],
	'GET'
);

$retorno = '[{"cliente":{"codigo_cliente":"5","nome_fantasia":"Dimmi Sueg Souza Santos","email":"dimmisueg@hotmail.com","razao_social":"Dimmi Sueg Souza Santos","cnpj_cpf":"033.391.773-17","endereco":"Rua Tiradentes","bairro":"Vila Eduardo","numero":"619","complemento":"","estado":"PE","cep":"56328-130","telefone1":"(87) 99603-2135","telefone2":"","cidade":"Petrolina","dados_entrega":{"nome_fantasia":"Dimmi Sueg Souza Santos","razao_social":"Dimmi Sueg Souza Santos","cnpj_cpf":"033.391.773-17","endereco":"Rua Tiradentes","bairro":"Vila Eduardo","numero":"619","complemento":"","estado":"PE","cep":"56328-130","cidade":"Petrolina"}},"produto":[{"codigo_produto":"0000000000982","descricao":"BLOCO HMPH A4 4X4 ANAMNESE NUTRICIONAL","quantidade":1,"valor_mercadoria":"15","valor_total":15,"valor_unitario":"15"}],"pedido":{"numero_pedido":4013,"quantidade_itens":1,"valor_total":15,"forma_pagamento":"cheque","data_venda":{"date":"2021-07-08 15:02:39.000000","timezone_type":3,"timezone":"America\/Sao_Paulo"},"observacao":"","notificacoes":"Pedido cancelado por falta de pagamento - tempo limite ultrapassado. Status do pedido alterado de Pagamento pendente para Cancelado.\n\rStatus do pedido alterado de Processando para Pagamento pendente.\n\rOs n\u00edveis de estoque aumentaram: BLOCO HMPH A4 4X4 ANAMNESE NUTRICIONAL (0000000000982) 44→45\n\rStatus do pedido alterado de Aguardando para Processando.\n\rAguardando compensa\u00e7\u00e3o do cheque Status do pedido alterado de Pagamento pendente para Aguardando.\n\rN\u00edveis de estoque reduzidos: BLOCO HMPH A4 4X4 ANAMNESE NUTRICIONAL (0000000000982) 45→44\n\r","numero_pedido_tsd":0,"forma_entrega":[{"codigo_forma_entrega":"free_shipping","descricao_forma_entrega":"Frete gr\u00e1tis","valor_total":"0.00","valor_taxas":"0"}]}}]';

$objIntegracaoWP->insereLog(
	[
		'url' => $endereco_wordpress,
		'body' => json_encode([]),
		'resultado' => $retorno,
		'data_registro' => date('Y-m-d H:i:s')
	]
);

$retorno = json_decode($retorno, true);

$arrConsultaUltimoCodigo = $objTSD->consultaCodigoUltimoPedidoCadastrado();
$codigo_pedido = $arrConsultaUltimoCodigo['ULTIMO_ID'];

$arrClientesGerados = [];
$arrPedidosGerados = [];

if (isset($retorno['success']) && $retorno['success'] == 'false') {
	die;
}


if (is_array($retorno)) {

	foreach ($retorno as $item) {
		
		if (!isset($item['pedido'])) {
			break;
		}
		
		if ($item['pedido']['numero_pedido_tsd'] > 0) {
		
			$objIntegracaoWP->insereLog(
				[
					'url' => '',
					'body' => json_encode([]),
					'resultado' => "O pedido do site: " . $item['pedido']['numero_pedido'] . " já foi importado com o número " . $item['pedido']['numero_pedido_tsd'],
					'data_registro' => date('Y-m-d H:i:s')
				]
			);
			continue;
		}
		
		$item['cliente']['cnpj_cpf'] = preg_replace('/\D/', '', $item['cliente']['cnpj_cpf']);
		$item['cliente']['cep'] = preg_replace('/\D/', '', $item['cliente']['cep']);
		$item['cliente']['telefone1'] = preg_replace('/\D/', '', $item['cliente']['telefone1']);
		$item['cliente']['telefone2'] = preg_replace('/\D/', '', $item['cliente']['telefone2']);
		
		$item['cliente']['codigo_municipio'] = '2930105';
		$item['cliente']['data_cadastro'] = date('Y-m-d');
		$item['cliente']['situacao'] = 'ATIVO';
		
		$arrConsultaCliente = $objTSD->consultaCliente(
			[
				'cnpj_cpf' => $item['cliente']['cnpj_cpf']
			]
		);		
		
		if ($arrConsultaCliente !== false) {
			$codigo_cliente = $arrConsultaCliente['ID_CLIENTE'];
		} else {
			$arrConsultaUltimoCodigo = $objTSD->consultaCodigoUltimoClienteCadastrado();
			$codigo_cliente = $arrConsultaUltimoCodigo['ULTIMO_ID'] + 1;
			$codigo_cliente_wp = $item['cliente']['codigo_cliente'];
			$item['cliente']['codigo_cliente'] = $codigo_cliente;
			
			$objTSD->insereCliente(
				[
				
				'codigo_cliente' => $item['cliente']['codigo_cliente'],
				'nome_fantasia' => $item['cliente']['nome_fantasia'],
				'razao_social' => $item['cliente']['razao_social'],			
				'cnpj_cpf' => $item['cliente']['cnpj_cpf'],
				'endereco'=> $item['cliente']['endereco'],
				'numero' => $item['cliente']['numero'],
				'complemento' => $item['cliente']['complemento'],
				'bairro' => $item['cliente']['bairro'],
				'cidade' => $item['cliente']['cidade'],
				'codigo_municipio' => $item['cliente']['codigo_municipio'],
				'estados' => $item['cliente']['estado'],		
				'cep' => $item['cliente']['cep'],			
				'telefone1' => $item['cliente']['telefone1'],
				'telefone2' => $item['cliente']['telefone2'],
				'data_cadastro' => $item['cliente']['data_cadastro'],
				'email' => $item['cliente']['email'],
				'situacao' => $item['cliente']['situacao']
				]
			);
			$arrClientesGerados[] = [
										'codigo_cliente_wp' => $item['cliente']['codigo_cliente'],
										'codigo_cliente_tsd' => $codigo_cliente
									];
		}
		
		$codigo_pedido_wp = $item['pedido']['numero_pedido'];
		$item['pedido']['codigo_pedido'] = ++$codigo_pedido;
		$item['pedido']['hora_venda'] = date('H:i:s', strtotime($item['pedido']['data_venda']['date']));
		$item['pedido']['status_venda'] = 'A';
		$item['pedido']['situacao'] = 'PENDENTE';
		$item['pedido']['cancelado'] = 'N';
		$item['pedido']['codigo_cliente'] = $codigo_cliente;
		$item['pedido']['nome_cliente'] = $item['cliente']['nome_fantasia'];
		$item['pedido']['cpf_cnpj_cliente'] = preg_replace('/\D/', '', $item['cliente']['cnpj_cpf']);
		$item['pedido']['observacao'] = $objUtils->removeAcentuacao('Pedido site: ' . $item['pedido']['numero_pedido'] . " " . $item['pedido']['observacao'] . $item['pedido']['notificacoes']);
		$item['pedido']['data_venda'] = date('Y-m-d', strtotime($item['pedido']['data_venda']['date']));
		$item['pedido']['id_usuario'] = $_ENV['ID_USUARIO_ORCAMENTO'];
		
		
		$objTSD->inserePedido($item['pedido']);
		
		$arrPedidosGerados[] = [
									'codigo_pedido_wp' => $codigo_pedido_wp,
									'codigo_pedido_tsd' => $codigo_pedido
								];		
		
		$arrConsultaUltimoCodigo = $objTSD->consultaCodigoUltimoPedidoItemCadastrado();
		$codigo_pedido_item = $arrConsultaUltimoCodigo['ULTIMO_ID'];
		$auxItem = 0;
		foreach ($item['produto'] as $produto) {
			
			$arrConsultaProduto = $objTSD->consultaProduto(
				[
					'gtin' => $produto['codigo_produto']
				]
			);
			
			$produto['codigo_item'] = ++$codigo_pedido_item;
			$produto['codigo_produto'] = $arrConsultaProduto['ID_PRODUTO'];
			$produto['codigo_pedido'] = $codigo_pedido;
			$produto['sequencia_item'] = ++$auxItem;
			$produto['valor_custo'] = $arrConsultaProduto['CUSTO'];
			$produto['movimenta_estoque'] = 'N';
			$produto['cancelado'] = 'N';

			$objTSD->inserePedidoItem($produto);
			
		}
	}
	
	$endereco_api_wordpress = $_ENV['ENDERECO_WORDPRESS'] . 'integracao/webservice/rest.php';
	if (!empty($arrClientesGerados)) {
		$retorno = $objUtils->callCurl($endereco_wordpress);
		$objUtils->callCurl($endereco_api_wordpress.'?acao=atualiza-cliente',json_encode($arrClientesGerados));
		
		$objIntegracaoWP->insereLog(
			[
				'url' => $endereco_api_wordpress.'?acao=atualiza-cliente',
				'body' => json_encode($arrClientesGerados),
				'resultado' => $retorno,
				'data_registro' => date('Y-m-d H:i:s')
			]
		);		
	}
	
	if (!empty($arrPedidosGerados)) {
		$retorno = $objUtils->callCurl($endereco_wordpress);
		$objUtils->callCurl($endereco_api_wordpress.'?acao=atualiza-pedido',json_encode($arrPedidosGerados));
		$objIntegracaoWP->insereLog(
			[
				'url' => $endereco_api_wordpress.'?acao=atualiza-pedido',
				'body' => json_encode($arrPedidosGerados),
				'resultado' => $retorno,
				'data_registro' => date('Y-m-d H:i:s')
			]
		);
	}
}
?>