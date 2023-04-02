<?php
	include_once "../common/version.php";
	include_once "../common/urlcode.php";
	include_once "../common/lte_db.php";
	include_once "../common/aiutility.php";
	
	function get_talking_points($url, $pro, $text, $count = 5, $max_tokens = 2000, $temperature = 1.0) {
		$head = substr($text, 0, 1500);
		
		$instru = "Create a list of " . $count . " points about this article from the perspective of a person concerned about climate change:" . $head;
		
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
	
	function suggest_angles($url, $pro, $text, $max_tokens = 2000, $temperature = 1.0) {
		$head = substr($text, 0, 1500);
	
		$instru = "Suggest an angle for a letter to the editor about this article from the perspective of someone concerned about climate change: " . $head;
	
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
			return '{input_len: ' . strval(strlen($text)) . ', error: ' . '"' . $returnString . '"}';
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

	
	$pro = $_GET['pro'];
	if ($pro == '0')
		$pro = false;
	else 
		$pro = true;


	$req = $_GET['req'];
	if ($req == 'tp') {
		echo get_talking_points($u, $pro, $innerText);
	}
	else if ($req = 'angles') {
		echo suggest_angles($u, $pro, $innerText);
	}
	else {
		echo ("Bad request. No request type was given.");
	}
?>