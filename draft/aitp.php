<?php
	include_once "../common/version.php";
	include_once "../common/urlcode.php";
	include_once "../common/lte_db.php";
	include_once "../common/aiutility.php";
	
	define("INPUT_SIZE", 1500);
	
	function sanitizeString($input) {
        // Define the allowed characters as a regular expression
        $allowedChars= '/[^a-zA-Z0-9\s\.,?!@#$%^&*()\-_+=;:"\'<>]/';
        // Remove any characters that are not allowed
        $sanitizedString = preg_replace($allowedChars, '', $input);

        // Return the sanitized string
        return $sanitizedString;
    }

	function get_talking_points($topic, $url, $pro, $text, $count = 5, $max_tokens = 800, $temperature = 1.0) {
		$head = substr(sanitizeString($text), 0, INPUT_SIZE);
	
		$conn = new LTE_DB();
		$key_phrase = $conn->fetch_key_ai_screen_phrase_for_topic($topic);
		$conn = null;
		if (is_null($key_phrase))
			$key_phrase = 'climate change';
	
		$instru = "Create a list of " . $count . " points about this article from the perspective of a person concerned about #key_phrase:" . $head;
		$instru = str_replace("#key_phrase", $key_phrase, $instru);
		
		$postData = array(
			"model" => "text-davinci-003",
			"prompt" => $instru,
			"max_tokens" => $max_tokens,
			"temperature" => $temperature
		);
		
		$returnString = fetch_from_openai(json_encode($postData));
		$decoded = json_decode($returnString);
		
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
			return '{input_len: ' . strval(strlen($text)) . ', error: ' . '"' . $returnString . '"}';
		}
	}
	
	function suggest_angles($topic, $url, $pro, $text, $max_tokens = 800, $temperature = 1.0) {
		$head = substr(sanitizeString($text), 0, INPUT_SIZE);
	

		$conn = new LTE_DB();
		$key_phrase = $conn->fetch_key_ai_screen_phrase_for_topic($topic);
		$conn = null;
		if (is_null($key_phrase))
			$key_phrase = 'climate change';
		
		$instru = "Suggest a clever and original angle for a letter to the editor from someone concerned with #key_phrase about this article : " . $head;
		
		$instru = "Imagine you are tasked with writing a 250-word letter-to-the-editor that offers a fresh viewpoint on a given newspaper article. You are very concerned about #key_phrase. Your objective is to explore uncharted territory, uncover hidden narratives, or challenge conventional wisdom.  Delve into unexplored angles, unconventional interpretations, or lesser-known implications. Here is the article: " . $head;
		
		
		
		$instru = str_replace("#key_phrase", $key_phrase, $instru);
	
		$postData = array(
			"model" => "text-davinci-003",
			"prompt" => $instru,
			"max_tokens" => $max_tokens,
			"temperature" => $temperature
		);
		
		$returnString = fetch_from_openai(json_encode($postData));
		$decoded = json_decode($returnString);
		
		if ($decoded && is_array($decoded->choices) && $decoded->choices[0]->text) {
			$decoded->choices[0]->fullResponse = $returnString;
			$decoded->choices[0]->text = getSubstringStartingWithFirstUppercase($decoded->choices[0]->text);
			return json_encode($decoded->choices[0]);
		}
		else {
			return '{input_len: ' . strval(strlen($text)) . ', error: ' . '"' . $returnString . ' . '  /*.  $instru*/ . '"}';
		}
	}
	
	function getSubstringStartingWithFirstUppercase($s) {
		$uppercaseFound = false;
		$substring = "";
		for ($i = 0; $i < strlen($s); $i++) {
			$char = $s[$i];
			if (ctype_upper($char) && !$uppercaseFound) {
				$uppercaseFound = true;
				$substring .= $char;
			} else if ($uppercaseFound) {
				$substring .= $char;
			}
		}
		return $substring;
	}	

# begin script
	
	$u = $_GET['u'];
	
	if ($u == null) {
		
		$z = $_GET['z'];
		$u = decode_url($z);
		
		if ($u == null) {
			echo "Missing URL. Nothing to do.\n";
			exit(0);
		}
	}
	
	$u = "https://ltesearch.org/read?u=" . $u;
	$content = read_html_from_url($u,"Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)");
	$dom = new DOMDocument();

	@$dom->loadHTML($content);
	$element = $dom->getElementById('main');

	$innerText = $element->textContent;

	$topic = $_GET["topic"];
	if (empty($topic)) {
		$topic = 'climate';
	}
	
	$pro = $_GET['pro'];
	if ($pro == '0')
		$pro = false;
	else 
		$pro = true;


	$req = $_GET['req'];
	if ($req == 'tp') {
		echo get_talking_points($topic, $u, $pro, $innerText);
	}
	else if ($req = 'angles') {
		echo suggest_angles($topic, $u, $pro, $innerText);
	}
	else {
		echo ("Bad request. No request type was given.");
	}
?>