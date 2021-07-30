<?php


namespace IntegracaoFirebirdWordpress\classes;
use Dotenv;

class Utils{
	public function __construct() {

	}
	
	
	function transformaEncode($string) {
		$string = utf8_encode($string);
		$encoding = 'UTF-8';
		return mb_convert_case($string, MB_CASE_LOWER, $encoding);	
	}

	function removeAcentuacao($id){
		$LetraProibi = Array(",",".","'","\"","&","|","!","#","$","¨","*","(",")","`","´","<",">",";","=","+","§","{","}","[","]","^","~","?","%");
		$special = Array('Á','È','ô','Ç','á','è','Ò','ç','Â','Ë','ò','â','ë','Ø','Ñ','À','Ð','ø','ñ','à','ð','Õ','Å','õ','Ý','å','Í','Ö','ý','Ã','í','ö','ã',
		'Î','Ä','î','Ú','ä','Ì','ú','Æ','ì','Û','æ','Ï','û','ï','Ù','®','É','ù','©','é','Ó','Ü','Þ','Ê','ó','ü','þ','ê','Ô','ß','‘','’','‚','“','”','„');
		$clearspc = Array('A','e','o','c','a','e','o','c','a','e','o','a','e','o','n','a','d','o','n','a','o','o','a','o','y','a','i','o','y','a','i','o','a',
		'i','a','i','u','a','i','u','a','i','u','a','i','u','i','u','','e','u','c','e','o','u','p','e','o','u','b','e','o','b','','','','','','');
		$newId = str_replace($special, $clearspc, $id);
		$newId = str_replace($LetraProibi, "", trim($newId));
		return $newId;
	}

    public function callCurl($url, $post = [], $metodo = 'POST') {
        $curl = curl_init();
		
		$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1).'/');
		$dotenv->load();
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 5000);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $metodo);
		if ($metodo == 'POST') {
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt(
			$curl,
			CURLOPT_HTTPHEADER,
			array(
				"Content-Type: application/json",
				'chave: ' . $_ENV['CHAVE_API_WORDPRESS']
			)
		);

		if (isset($_ENV['PROXY']) && !empty($_ENV['PROXY'])) {
			$re = '/((http|ftp|https):\/\/)(.+):(.+)@(.+):(.+)/m';
			$str = $_ENV['PROXY'];
			preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
			if (!empty($matches)) {
				curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
				curl_setopt($curl, CURLOPT_PROXY, $matches[0][1].$matches[0][5]);
				curl_setopt($curl, CURLOPT_PROXYPORT, $matches[0][6]);
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, $matches[0][3].':'.$matches[0][4]);
			}
		}
        
        $response = curl_exec($curl);
		
		
		if (curl_error($curl)) {
			$error_msg = curl_error($curl);
		}		
		
		if (isset($error_msg)) {
			print "Erro no curl";
			print $error_msg;
		}
		
		curl_close($curl);
        
        
        return $response;
    }
}