<?php

namespace IntegracaoFirebirdWordpress\classes;

use PDO;
use Dotenv;

class Conexao
{

  /*  
    * Atributo estático para instância do PDO  
    */
  protected static $instance;
  protected static $instance_db_integracao;

  /*  
    * Escondendo o construtor da classe  
  */
  private function __construct()
  { }

  public static function getInstanceFirebird($db_integracao = false)
  {
	  
	$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1).'/');
	$dotenv->load();
	
	$instancia = self::$instance;
	if ($db_integracao == true) {
		$instancia = self::$instance_db_integracao;
	}
	
	if (empty($instancia)) {

	  $db_info = array(
		"db_host" => $_ENV['DB_HOST'],
		"db_user" => $_ENV['DB_USUARIO'],
		"db_pass" => $_ENV['DB_SENHA'],
		"db_name" => $db_integracao == false ? $_ENV['DB_BASE'] : $_ENV['DB_BASE_INTEGRACAO'],
		"db_charset" => "UTF-8"
	  );

	  try {
		$opcoes = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8', PDO::ATTR_PERSISTENT => TRUE);  
		$instancia = new PDO("firebird:dbname=" . $db_info['db_name'] . "; host=" . $db_info['db_host'] . "; charset=" . $db_info['db_charset'] . ";", $db_info['db_user'], $db_info['db_pass'], $opcoes);
		$instancia->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		// self::$instance->query('SET NAMES utf8');
		// self::$instance->query('SET CHARACTER SET utf8');
	  } catch (PDOException $error) {
		echo $error->getMessage();
	  }
	}

	return $instancia;
  }
  
	public static function debugSQL($string,$data) {
		
		$data = array_values($data);
		
		preg_match_all('/(:[_A-Za-z0-9]+)/',$string, $matches);
		
		$sql_values = '';
		if (is_array($matches)) {
			$count = count($matches[0]);
			$aux = 1;
			foreach ($matches[0] as $key => $value) {
				$adiciona_virgula = ',';
				
				$sql_values .= sprintf("'%s'%s ", $data[$key], ($aux < $count) ? ',' : '').chr(13).chr(10);
				$aux++;
			}
		}
		
		$string = preg_replace('/VALUES[A-Za-z0-9 \n\t\(:_,\)]+/',' VALUES ( '.$sql_values.')',$string);
		
		return $string;
	}
}
