<?php
	include "../common/version.php";
	include "../common/urlcode.php";
	include "../common/lte_db.php";
	
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
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			$auth_header
		]);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	function get_talking_points($url, $pro, $count = 5, $max_tokens = 800, $temperature = 1.0) {
		
		if ($pro) {
			$instru = "Create a list of " . $count . " points in support of the views in the article at the following URL: " . $url;
		}
		else {
			$instru = "Create a list of " . $count . " points critical of the views in the article at the following URL: " . $url;
		}
		
		$postData = array(
			"model" => "text-davinci-003",
			"prompt" => $instru,
			"max_tokens" => $max_tokens,
			"temperature" => $temperature
		);

		$decoded = json_decode(fetch_from_openai(json_encode($postData)));
		
		if ($decoded && is_array($decoded->choices) && $decoded->choices[0]->text) {
			$output = $decoded->choices[0]->text;
			$splat = preg_split("/\s\d\./", $output); // split on space number period
			
			if (count($splat) > 5) {
				unset($splat[0]);
			}
			
			$json = json_encode($splat);
			return $json;
		}
		else {
			return "";
		}
	}
	
	function suggest_angles($url, $pro, $max_tokens = 800, $temperature = 1.0) {
		$instru = "Suggest an angle for a letter to the editor about the article found at the following URL: " . $url . ". ";
		if ($pro) {
			$instru = $instru . "The letter should argue in support of the article's content.";
		}
		else {
			$instru = $instru . "The letter should argue against the article's content.";
		}
		
		$postData = array(
			"model" => "text-davinci-003",
			"prompt" => $instru,
			"max_tokens" => $max_tokens,
			"temperature" => $temperature
		);
		
		$decoded = json_decode(fetch_from_openai(json_encode($postData)));
		
		if ($decoded && is_array($decoded->choices) && $decoded->choices[0]->text) {
			return json_encode($decoded->choices[0]);
		}
		else {
			return '{error: ' . '"' . $postData . '"}';
		}
	}
	
	$u = $_GET['u'];
	
	if ($u == null) {
		
		$z = $_GET['z'];
		$u = decode_url($z);
		
		if ($u == null) {
			echo "Missing URL. Nothing to do.\n";
			exit(0);
		}
	}
	
	$pro = $_GET['pro'];
	if ($pro == '0')
		$pro = false;
	else 
		$pro = true;


	$req = $_GET['req'];
	if ($req == 'tp') {
		echo get_talking_points($u, $pro);
	}
	else if ($req = 'angles') {
		echo suggest_angles($u, $pro);
	}
	else {
		echo ("Bad request. No request type was given.");
	}
?>