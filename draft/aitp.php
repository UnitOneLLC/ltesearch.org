<?php
	include_once "../common/version.php";
	include_once "../common/urlcode.php";
	include_once "../common/lte_db.php";
	include_once "../common/aiutility.php";
	
	define("INPUT_SIZE", 3500);
	
	function sanitizeString($input) {
        // Define the allowed characters as a regular expression
        $allowedChars= '/[^a-zA-Z0-9\s\.,?!@#$%^&*()\-_+=;:"\'<>]/';
        // Remove any characters that are not allowed
        $sanitizedString = preg_replace($allowedChars, '', $input);

        // Return the sanitized string
        return $sanitizedString;
    }

	function get_ai_draft_prompt() {
		try {
			$conn = new LTE_DB();
			$prompt = $conn->get_parameter("ai_draft_prompt");
			$conn = null;
		}
		catch (PDOException $e) {
			$conn = null;
			error_log("PDOException in get_ai_draft_prompt");
			$prompt = null;
		}
		
		return $prompt;
	}

	function get_talking_points($topic, $url, $pro, $text, $count = 5, $max_tokens = 800, $temperature = 1.0) {
		return "error obsolete"; 
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
	
	function extractToTheEditorSubstring($s) {
		$result = $s;

		$phrases = [
			"To the Editor:",
			"To the Editor,",
			"Dear Editor:",
			"Dear Editor,"
		];
		
		foreach ($phrases as $key_phrase) {
			$position = stripos($s, $key_phrase);		
			if ($position !== false) {
				// If the phrase is found, extract the substring from the position where the phrase starts.
				$result = substr($s, $position + strlen($key_phrase) + 1);
				break;
			} 
		}
		
		return $result;
	}
	
# begin script
	
	$u = @$_GET['u'];
	
	if ($u == null) {
		
		$z = $_GET['z'];
		$u = decode_url($z);
		
		if ($u == null) {
			echo "Missing URL. Nothing to do.\n";
			exit(0);
		}
	}
	
	$u = "https://ltesearch.org/read?u=" . $u;
//	$content = read_html_from_url($u,"Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)");
	$content = file_get_contents($u);
	$dom = new DOMDocument();

	@$dom->loadHTML($content);
	$element = $dom->getElementById('main');

	$innerText = $element->textContent;

	$topic = $_GET["topic"];
	if (empty($topic)) {
		$topic = 'climate';
	}
	
	$pro = $_GET['pro'];
	if ($pro == '0') {
		$pro = false;
	}
	else {
		$pro = true;
	}

	$tp_only = isset($_GET['tp-only']) ? $_GET['tp-only'] : null;

	$req = $_GET['req'];
	if ($req == 'tp') {
		echo get_talking_points($topic, $u, $pro, $innerText);
	}
	else if ($req = 'angles') {
		@$extra = $_GET['extra'];
		echo suggest_angles($tp_only, $topic, $u, $pro, $innerText, $extra);
	}
	else {
		echo ("Bad request. No request type was given.");
	}
?>