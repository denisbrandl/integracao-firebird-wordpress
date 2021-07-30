<?php

namespace IntegracaoFirebirdWordpress\classes;

use IntegracaoFirebirdWordpress\classes\Conexao;
use PDO;

class TSD {
	
	public $ultima_execucao;
	
	public function consultaProdutos() {
		$db = Conexao::getInstanceFirebird();

		$sql = "
				SELECT 
					p.*, pg.GRUPO AS nome_grupo
				FROM 
					PRODUTOS p
				LEFT JOIN 
					PRODUTOS_GRUPO pg ON (p.GRUPO = pg.ID)
				WHERE
					p.DT_ULTIMO_MOVIMENTO >= '{$this->ultima_execucao}'
			";
		$stm = $db->prepare($sql);
		
		$stm->execute();

		return $stm->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function consultaCodigoUltimoClienteCadastrado() {
		$db = Conexao::getInstanceFirebird();

		$sql = "
				SELECT 
					max(ID_CLIENTE) as ULTIMO_ID					
				FROM 
					CLIENTES
			";

		$stm = $db->prepare($sql);
		
		$stm->execute();

		return $stm->fetch(PDO::FETCH_ASSOC);		
	}
	
	public function consultaCliente($arrFiltro = array()) {
		$db = Conexao::getInstanceFirebird();

		$sql = "
				SELECT 
						ID_CLIENTE,
						CLIENTE,
						RAZ_SOCIAL,
						CPF_CNPJ,
						LOGRADOURO,
						NUMERO,
						COMPLEMENTO,
						BAIRRO,
						MUNICIPIO,
						CODIGO_MUNICIPIO,
						UF,
						CEP,
						FONE,
						CELULAR,
						DT_CADASTRO,
						EMAIL,
						STATUS
				FROM 
					CLIENTES
			";


		$where = '';
		$and = '';
		
		if (count($arrFiltro) > 0) {
			if (isset($arrFiltro['cnpj_cpf'])) {
				$where = sprintf(" WHERE CPF_CNPJ = '%s'", $arrFiltro['cnpj_cpf']);
				$and = ' AND ';
			}
		}
		
		$sql .= $where;
		
		$stm = $db->prepare($sql);
		
		$stm->execute();

		return $stm->fetch(PDO::FETCH_ASSOC);		
	}	
	
	public function insereCliente($campos = array()) {
		
		try {
			
			unset($campos['dados_entrega']);
			
			$db = Conexao::getInstanceFirebird();

			$sql = 'INSERT INTO CLIENTES (
						ID_CLIENTE,
						CLIENTE,
						RAZ_SOCIAL,
						CPF_CNPJ,
						LOGRADOURO,
						NUMERO,
						COMPLEMENTO,
						BAIRRO,
						MUNICIPIO,
						CODIGO_MUNICIPIO,
						UF,
						CEP,
						FONE,
						CELULAR,
						DT_CADASTRO,
						EMAIL,
						STATUS
					) VALUES
					(
						:ID_CLIENTE,
						:CLIENTE,
						:RAZ_SOCIAL,
						:CPF_CNPJ,
						:LOGRADOURO,
						:NUMERO,
						:COMPLEMENTO,
						:BAIRRO,
						:MUNICIPIO,
						:CODIGO_MUNICIPIO,
						:UF,
						:CEP,
						:FONE,
						:CELULAR,
						:DT_CADASTRO,
						:EMAIL,
						:STATUS
					)';

			$stm = $db->prepare($sql);

			$stm->bindParam(':ID_CLIENTE', $campos['codigo_cliente']);
			$stm->bindParam(':CLIENTE', $campos['nome_fantasia']);
			$stm->bindParam(':RAZ_SOCIAL', $campos['razao_social']);			
			$stm->bindParam(':CPF_CNPJ', $campos['cnpj_cpf']);
			$stm->bindParam(':LOGRADOURO', $campos['endereco']);
			$stm->bindParam(':NUMERO', $campos['numero']);
			$stm->bindParam(':COMPLEMENTO', $campos['complemento']);
			$stm->bindParam(':BAIRRO', $campos['bairro']);
			$stm->bindParam(':MUNICIPIO', $campos['cidade']);
			$stm->bindParam(':CODIGO_MUNICIPIO', $campos['codigo_municipio']);
			$stm->bindParam(':UF', $campos['estados']);		
			$stm->bindParam(':CEP', $campos['cep']);			
			$stm->bindParam(':FONE', $campos['telefone1']);
			$stm->bindParam(':CELULAR', $campos['telefone2']);
			$stm->bindParam(':DT_CADASTRO', $campos['data_cadastro']);
			$stm->bindParam(':EMAIL', $campos['email']);
			$stm->bindParam(':STATUS', $campos['situacao']);
			
			$a = $stm->execute();
			
		} catch (Exception $e) {
            $stm->debugDumpParams();
			print $e->getMessage();
		}
	}
	
	
	public function inserePedido($campos = array()) {
		try {
			$db = Conexao::getInstanceFirebird();

			$sql = 'INSERT INTO ORCAMENTO (
						ID,
						ID_CLIENTE,
						ID_USUARIO,
						HORA_VENDA,
						VALOR_FINAL,
						TOTAL_PRODUTOS,
						STATUS_VENDA,
						SITUACAO,
						NOME_CLIENTE,
						CPF_CNPJ_CLIENTE,
						OBS,
						DATA_VENDA,
						CANCELADO
					) VALUES 
					(
						:ID,
						:ID_CLIENTE,
						:ID_USUARIO,
						:HORA_VENDA,
						:VALOR_FINAL,
						:TOTAL_PRODUTOS,
						:STATUS_VENDA,
						:SITUACAO,
						:NOME_CLIENTE,
						:CPF_CNPJ_CLIENTE,
						:OBS,
						:DATA_VENDA,
						:CANCELADO
					)';

			$stm = $db->prepare($sql);
			$stm->bindParam(':ID', $campos['codigo_pedido']);
			$stm->bindParam(':ID_CLIENTE', $campos['codigo_cliente']);
			$stm->bindParam(':ID_USUARIO', $campos['id_usuario']);
			$stm->bindParam(':HORA_VENDA', $campos['hora_venda']);
			$stm->bindParam(':VALOR_FINAL', $campos['valor_total']);
			$stm->bindParam(':TOTAL_PRODUTOS', $campos['valor_total']);
			$stm->bindParam(':STATUS_VENDA', $campos['status_venda']);
			$stm->bindParam(':SITUACAO', $campos['situacao']);
			$stm->bindParam(':NOME_CLIENTE', $campos['nome_cliente']);
			$stm->bindParam(':CPF_CNPJ_CLIENTE', $campos['cpf_cnpj_cliente']);
			$stm->bindParam(':OBS', $campos['observacao']);
			$stm->bindParam(':DATA_VENDA', $campos['data_venda']);
			$stm->bindParam(':CANCELADO', $campos['cancelado']);
			
			$stm->execute();
			
		} catch (Exception $e) {
            $stm->debugDumpParams();
			print $e->getMessage();
		}
	}
	
	public function inserePedidoItem($campos = array()) {
		try {
			$db = Conexao::getInstanceFirebird();

			$sql = 'INSERT INTO ORCAMENTO_ITENS (
						ID,
						ID_PRODUTO,
						ID_ORCAMENTO,
						ITEM,
						QUANTIDADE,
						VALOR_UNITARIO,
						VALOR_CUSTO,
						VALOR_PRODUTOS,
						TOTAL_ITEM,
						MOVIMENTA_ESTOQUE,
						CANCELADO
					) VALUES 
					(
						:ID,
						:ID_PRODUTO,
						:ID_ORCAMENTO,
						:ITEM,
						:QUANTIDADE,
						:VALOR_UNITARIO,
						:VALOR_CUSTO,
						:VALOR_PRODUTOS,
						:TOTAL_ITEM,
						:MOVIMENTA_ESTOQUE,
						:CANCELADO
					)';

			$stm = $db->prepare($sql);
			$stm->bindParam(':ID', $campos['codigo_item']);
			$stm->bindParam(':ID_PRODUTO', $campos['codigo_produto']);
			$stm->bindParam(':ID_ORCAMENTO', $campos['codigo_pedido']);
			$stm->bindParam(':ITEM', $campos['sequencia_item']);
			$stm->bindParam(':QUANTIDADE', $campos['quantidade']);
			$stm->bindParam(':VALOR_UNITARIO', $campos['valor_unitario']);
			$stm->bindParam(':VALOR_CUSTO', $campos['valor_custo']);
			$stm->bindParam(':VALOR_PRODUTOS', $campos['valor_mercadoria']);
			$stm->bindParam(':TOTAL_ITEM', $campos['valor_total']);
			$stm->bindParam(':MOVIMENTA_ESTOQUE', $campos['movimenta_estoque']);
			$stm->bindParam(':CANCELADO', $campos['cancelado']);
			
			$stm->execute();
			
		} catch (Exception $e) {
            $stm->debugDumpParams();
			print $e->getMessage();
		}
	}
	
	public function consultaProduto($arrFiltro = array()) {
		$db = Conexao::getInstanceFirebird();

		$sql = "
				SELECT 
						ID_PRODUTO,
						GTIN,
						PRODUTO,
						CUSTO,
						UNIDADE_COMPRA
				FROM 
					PRODUTOS
			";


		$where = '';
		$and = '';
		
		if (count($arrFiltro) > 0) {
			if (isset($arrFiltro['gtin'])) {
				$where = sprintf(" WHERE GTIN = '%s'", $arrFiltro['gtin']);
				$and = ' AND ';
			}
		}
		
		$sql .= $where;
		
		$stm = $db->prepare($sql);
		
		$stm->execute();

		return $stm->fetch(PDO::FETCH_ASSOC);		
	}		
	
	public function consultaCodigoUltimoPedidoCadastrado() {
		$db = Conexao::getInstanceFirebird();

		$sql = "
				SELECT 
					max(ID) as ULTIMO_ID					
				FROM 
					ORCAMENTO
			";

		$stm = $db->prepare($sql);
		
		$stm->execute();

		return $stm->fetch(PDO::FETCH_ASSOC);		
	}
	
	public function consultaCodigoUltimoPedidoItemCadastrado() {
		$db = Conexao::getInstanceFirebird();

		$sql = "
				SELECT 
					max(ID) as ULTIMO_ID					
				FROM 
					ORCAMENTO_ITENS
			";

		$stm = $db->prepare($sql);
		
		$stm->execute();

		return $stm->fetch(PDO::FETCH_ASSOC);		
	}	
}