<?php

namespace IntegracaoFirebirdWordpress\classes;

use IntegracaoFirebirdWordpress\classes\Conexao;
use PDO;

class IntegracaoWP {
	
	public function consultaConfiguracao($arrFiltro = array()) {
		$db = Conexao::getInstanceFirebird($instance_db_integracao = true);

		$sql = "
				SELECT 
						CONFIG_NAME,
						CONFIG_VALUE
				FROM 
					CONFIGURACOES
			";


		$where = '';
		$and = '';
		
		if (count($arrFiltro) > 0) {
			if (isset($arrFiltro['config_value'])) {
				$where = sprintf(" WHERE CONFIG_NAME = '%s'", $arrFiltro['config_value']);
				$and = ' AND ';
			}
		}
		
		$sql .= $where;
		
		$stm = $db->prepare($sql);
		
		$stm->execute();

		return $stm->fetch(PDO::FETCH_ASSOC);		
	}
	
	public function atualizaConfiguracao($campos = array(), $arrCondicoes) {
		
		try {
			$db = Conexao::getInstanceFirebird(true);

			$sql = 'UPDATE CONFIGURACOES SET ';
			
			foreach ($campos as $key => $value) {
				$sql .= $key . " = '" . $value . "'";
			}
			
			$sql .= ' WHERE ';
			foreach ($arrCondicoes as $key => $value) {
				$sql .= $key . " = '" . $value . "'";
			}

			$stm = $db->prepare($sql);			
			$stm->execute();
			
		} catch (Exception $e) {
            $stm->debugDumpParams();
			print $e->getMessage();
		}
	}	
}