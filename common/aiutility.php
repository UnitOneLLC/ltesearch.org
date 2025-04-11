<?php
	include_once "../common/version.php";
	include_once "../common/lte_db.php";
	
	define("OPENAI_MODEL", "gpt-4o-mini");
	define("OPENAI_INSTRUCT_MODEL", "gpt-3.5-turbo-instruct");
	define("OPEN_AI_COMPLETION", "https://api.openai.com/v1/completions");
	define("OPEN_AI_CHAT_COMPLETION", "https://api.openai.com/v1/chat/completions");

	define("CLAUDE_MODEL", "claude-3-7-sonnet-20250219");	
//	define("CLAUDE_MODEL", "claude-3-5-sonnet-20241022");
//	define("CLAUDE_MODEL", "claude-3-sonnet-20240229");
	define("CLAUDE_ENDPOINT", 'https://api.anthropic.com/v1/messages');
	
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
	
	function get_claude_api_key() {
		try {
			$conn = new LTE_DB();
			$apiKey = $conn->get_parameter("claude-api-key");
		}
		catch (PDOException $e) {
			$conn = null;
			echo("exception "); var_dump($e);
			return null;
		}
		return $apiKey;
	}
	
	function get_claude_sleep_time() { //usec
		try {
			$conn = new LTE_DB();
			$usec = $conn->get_parameter("claude_sleep_usec");
		}
		catch (PDOException $e) {
			$conn = null;
			echo("exception "); var_dump($e);
			return null;
		}
		return (int)$usec;
	}

	function query_ai($query) {
		try {
			$conn = new LTE_DB();
			$engine = $conn->get_parameter("ai_engine");
			$conn = null;
		}
		catch (PDOException $e) {
			$conn = null;
			$key = null;
			error_log("PDOException in query_ai");
		}
		if ($engine == 'openai') {
			return query_ai_openai($query);
		}
		else if ($engine == 'claude') {
			return query_ai_claude($query);
		}
		else {
			error_log("AI engine parameter not found.");
			return "Unavailable at this time";
		}
	}

	function query_ai_claude($query) {
		$apiKey = get_claude_api_key();

		$url = CLAUDE_ENDPOINT;

		$messages = [
			[
				'role' => 'user',
				'content' => $query
			]
		];
		
		// Convert messages to JSON
		$data = [
			'model' => CLAUDE_MODEL,
			'max_tokens' => 2048,
			'messages' => $messages
		];
		
		
		$jsonData = json_encode($data);        
//		error_log($jsonData);
		
		// Setup the curl data
		$curl = curl_init();
		
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $jsonData,
			CURLOPT_HTTPHEADER => [
				'x-api-key: ' . $apiKey,
				'anthropic-version: 2023-06-01',
				'content-type: application/json'
			],
		]);      
		// Execute curl
		$response = curl_exec($curl);
		if (stripos($response, "error")) {
			error_log("response from claude: $response");
		}
		curl_close($curl);
		
		// Decode the API response
		$responseData = json_decode($response, true);        
		// Extract the assistant's reply
		$content = $responseData['content'][0]['text'];
		usleep(get_claude_sleep_time());
		return $content;
	}


	function query_ai_openai($query) {
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

	function suggest_angles($tp_only, $topic, $url, $pro, $text, $extra, $max_tokens = 800, $temperature = 1.0) {
		$head = substr(sanitizeString($text), 0, INPUT_SIZE);

		$conn = new LTE_DB();
		$key_phrase = $conn->fetch_key_ai_screen_phrase_for_topic($topic);
		$conn = null;
		if (is_null($key_phrase))
			$key_phrase = 'climate change';
		if (empty($extra)) 
			$extra="";
		$instru = get_ai_draft_prompt() . " " . $extra;
		if ($tp_only == "true") {
			$instru .= "Do not generate a letter. Only generate an HTML-formatted bullet list of talking points. Emit just the HTML-formatted list without introduction.";
		}
		$instru .= " Here is the article: " . $head;		
		$instru = str_replace("#key_phrase", $key_phrase, $instru);

		$response = query_ai($instru);
		return json_encode(array("text" => extractToTheEditorSubstring($response)));
	}
?>