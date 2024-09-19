<?php
	include_once "../common/version.php";
	include_once "../common/lte_db.php";
	
	define("OPENAI_MODEL", "gpt-4o-mini");
	define("OPENAI_INSTRUCT_MODEL", "gpt-3.5-turbo-instruct");
	define("OPEN_AI_COMPLETION", "https://api.openai.com/v1/completions");
	define("OPEN_AI_CHAT_COMPLETION", "https://api.openai.com/v1/chat/completions");
	
	function get_openai_api_key() {
		try {
			$conn = new LTE_DB();
			$key = $conn->get_parameter("openai_key");
			$conn = null;
		}
		catch (PDOException $e) {
			$conn = null;
			$key = null;
			error_log("PDOException in get_api_key");
		}
		
		return $key;
	}

	function query_ai($query) {
		$ch = curl_init();
		
		$url = OPEN_AI_CHAT_COMPLETION;
		
		$api_key = get_openai_api_key();
		
		$post_fields = array(
			"model" => OPENAI_MODEL,
			"messages" => array(
				array(
					"role" => "user",
					"content" => $query
				)
			),
			"max_tokens" => 1024,
			"temperature" => 1.0
		);
		
		$header  = [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $api_key
		];
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error: ' . curl_error($ch);
		}
		curl_close($ch);
		
		$response = json_decode($result);
		return $response->choices[0]->message->content;  
	}

	function fetch_from_openai_completion($instru) {
		$key = get_openai_api_key();
		$payload = array(
			"model" => OPENAI_INSTRUCT_MODEL,
			"prompt" => $instru,
			"max_tokens" => 512,
			"temperature" => 1.0
		);
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
		$encoded_postData = json_encode($payload);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $encoded_postData);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$decoded = json_decode($result);
		$answerText = "ERROR";
		if ($decoded && is_array($decoded->choices) && $decoded->choices[0]->text) {
			$answerText = trim($decoded->choices[0]->text);
		}
		return $answerText;
	}


?>