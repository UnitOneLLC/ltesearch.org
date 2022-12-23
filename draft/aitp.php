<?php
	include "../common/version.php";
	include "../common/urlcode.php";
	
	define("OPEN_AI_COMPLETION", "https://api.openai.com/v1/completions");
	
	function fetch_from_openai($payload) {
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
			'Authorization: Bearer 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'
		]);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
		$result = curl_exec($ch);
		return $result;
	}

	function get_talking_points($url, $pro, $count = 5, $max_tokens = 800, $temperature = 1.0) {
		
		if ($pro) {
			$instru = "Create a list of " . $count . " points in support of the views in this article: " . $url;
		}
		else {
			$instru = "Create a list of " . $count . " points critical of the views in this article: " . $url;
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
			$splat = preg_split("/\d\./", $output);
			
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
	
	echo get_talking_points($u, $pro);
?>