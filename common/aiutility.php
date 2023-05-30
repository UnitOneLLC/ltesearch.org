<?php
	include_once "../common/version.php";
	include_once "../common/lte_db.php";
	
	define("OPEN_AI_COMPLETION", "https://api.openai.com/v1/completions");
	
	function get_api_key() {
		try {
			$conn = new LTE_DB();
			$keys = $conn->get_openai_api_key();
			$conn = null;
			
			foreach($keys as $k) {
				$key = $k;
				break;
			}
		}
		catch (PDOException $e) {
			$conn = null;
			$key = "PDO EXCEPTION";
		}
		
		return $key;
	}
	
	function fetch_from_openai($payload) {
		$key = get_api_key();
		$auth_header = 'Authorization: Bearer ' . $key;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:97.0) Gecko/20100101 Firefox/97.0");
		curl_setopt($ch, CURLOPT_URL, OPEN_AI_COMPLETION);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 40);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			$auth_header
		]);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	

?>