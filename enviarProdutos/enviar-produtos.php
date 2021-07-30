<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';
use IntegracaoFirebirdWordpress\classes\TSD;
use IntegracaoFirebirdWordpress\classes\IntegracaoWP;
use IntegracaoFirebirdWordpress\classes\Utils;


$objIntegracaoWP		= new IntegracaoWP;
$objTSD					= new TSD;
$objUtils				= new Utils;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$endereco_wordpress = $_ENV['ENDERECO_WORDPRESS'] . 'integracao/webservice/rest.php?acao=atualiza-produto';

$arrUltimaExecucao = $objIntegracaoWP->consultaConfiguracao(
[
	'config_value' => 'ultima_execucao'
]
);

$objTSD->ultima_execucao = $arrUltimaExecucao['CONFIG_VALUE'];


$consultaProduto = $objTSD->consultaProdutos();
$arrProdutos = [];
foreach ($consultaProduto as $detalhes_produto) {
	
	$arrProduto = array();
	
	$detalhes_produto = (array) $detalhes_produto;
	
	$arrProduto['dados_gerais']['id_original'] = $detalhes_produto['ID_PRODUTO'];
	$arrProduto['dados_gerais']['sku'] = $detalhes_produto['GTIN'];
	$arrProduto['dados_gerais']['nome'] = utf8_encode($detalhes_produto['PRODUTO']);	
	$arrProduto['dados_gerais']['descricao_completa'] = strip_tags($detalhes_produto['DESCRICAO_COMPRA']);	
	$arrProduto['dados_gerais']['altura'] = '';	
	$arrProduto['dados_gerais']['largura'] = '';	
	$arrProduto['dados_gerais']['peso'] = '';	
	$arrProduto['dados_gerais']['profundidade'] = '';	
	$arrProduto['dados_gerais']['preco_normal'] = $detalhes_produto['VALOR_VENDA'];	
	$arrProduto['dados_gerais']['quantidade'] = $detalhes_produto['ESTOQUE'];
	$arrProduto['dados_gerais']['disponivel'] = 'INATIVO';
	
	$arrProduto['categorias'][] = [
									'nome' => utf8_encode($detalhes_produto['NOME_GRUPO']),
									'descricao' => ''
								];
	
	$arrProdutos[] = $arrProduto;
}

if (count($arrProdutos) > 0) {
	echo "<hr><h1>Dados de envio </h1>";
	echo "<pre> ".json_encode($arrProdutos)."</pre>";
	$retorno = $objUtils->callCurl($endereco_wordpress,json_encode($arrProdutos));

	echo "<hr><h1>Dados de retorno </h1>";

	echo "<pre> ".$retorno."</pre>";

	echo "Integração concluida";
}

$objIntegracaoWP->atualizaConfiguracao(
	['config_value' => date('Y-m-d H:i:s')],
	['config_name' => 'ultima_execucao']
);


?>