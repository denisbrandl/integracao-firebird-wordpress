<?php
require_once(dirname(__DIR__).'/classes/ImportacaoWordpress.php');
$objImportacaoWordpress = new ImportacaoWordpress();
$mensagem_retorno = "";	
$retorno = [];
$requisicao =$_SERVER["REQUEST_METHOD"];
$variaveis = file_get_contents("php://input");

$arrOpcoesPlugin = get_option('firebird_name');
$chave_configurada = $arrOpcoesPlugin['chave_token_api'];

if (!isset($_SERVER['HTTP_CHAVE']) || empty($_SERVER['HTTP_CHAVE']) ) {
	die('Sem chave');
}

$chave = $_SERVER['HTTP_CHAVE'];

if ($chave !== $chave_configurada) {
	header('Content-Type: application/json');
	echo json_encode(['retorno' => 'Chave incorreta!']);
	exit;
}

if (!isset($_GET['acao']) || empty($_GET['acao']) ) {
	header('Content-Type: application/json');
	echo json_encode(['retorno' => 'Não foi definido a ação']);
	exit;
}
$acao = $_GET['acao'];

$dt_filtro = '';
if (isset($_GET['dt_filtro']) && !empty($_GET['dt_filtro']) ) {
$dt_filtro = $_GET['dt_filtro'];
}

if (empty($variaveis) && $requisicao == 'POST') {
	echo 'Não foi enviado as informações do produto no Body';
	return;
} else if ($requisicao == 'POST') {

	if ($objImportacaoWordpress->validaJson($variaveis) == false) {
		die('Json invalido');
	}
	$dados = json_decode($variaveis,true);
}

switch ($requisicao) {
	case 'POST':
		switch ($acao) {
			case 'atualiza-produto':
				$retorno = $objImportacaoWordpress->lerJson($dados);
				header('Content-Type: application/json');
				echo json_encode(['retorno' => $retorno['msgRetorno']]);
				exit;
			break;
			
			case 'atualiza-cliente':
				$retorno = $objImportacaoWordpress->atualizaCliente($dados);
				header('Content-Type: application/json');
				echo json_encode(['retorno' => $retorno['msgRetorno']]);
				exit;
			break;
			
			case 'atualiza-pedido':
				$retorno = $objImportacaoWordpress->atualizaPedido($dados);
				header('Content-Type: application/json');
				echo json_encode(['retorno' => $retorno['msgRetorno']]);
				exit;
			break;
			
			default:
				echo 'Ação inválida';
			break;
		}
	break;	
	
	case 'GET':
		switch ($acao) {
			case 'importa-pedido':
				if (empty($dt_filtro)) {
					echo 'Esta ação necessita que seja passado o parâmetro dt_filtro com a data de filtro';
				}
				$retorno = $objImportacaoWordpress->exportarPedidos($dt_filtro);
				header('Content-Type: application/json');
				echo json_encode($retorno);
				return;
			break;
			
			default:
				echo 'Ação inválida';
				return;
			break;			
		}
	break;
	
	default:
		echo 'Tipo de requisição inválido, necessário utilizar GET/POST';
	break;
}