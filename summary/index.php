<?php
	include "../common/version.php";
	include "../common/lte_db.php";
	include "../common/urlcode.php";
	define("OPEN_AI_COMPLETION", "https://api.openai.com/v1/completions");
	
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

#===================================================
	$summary = "Summary is not available.";
	$length = 400;
	error_reporting(E_ERROR | E_PARSE);
	
	try {
		$u = $_GET['u'];
		
		if ($u == null) {
			
			$z = $_GET['z'];
			$u = decode_url($z);
			
			if ($u == null) {
				echo "no URL\n";
				exit(0);
			}
		}
		
		$max_tokens = $length;
		$temperature = 1.0;
		$instru = "Summarize the article at : " . $u . ". Include the title of the article and its URL in the summary.";
		$postData = array(
			"model" => "text-davinci-003",
			"prompt" => $instru,
			"max_tokens" => $max_tokens,
			"temperature" => $temperature
		);
		
		$reqText = json_encode($postData);
		$textResult = fetch_from_openai($reqText);
		$decoded = json_decode($textResult);
		
		// Close the curl connection
		curl_close($ch);		
		
		// Check for errors
		if (curl_errno($ch)) {
			// There was an error executing the curl request
			$summary = $summary . "( Error: " . curl_error($ch) . ")";
			echo "Error: " . curl_error($ch);
		} else {
			if ($decoded && is_array($decoded->choices) && $decoded->choices[0]->text) {
				$summary = $decoded->choices[0]->text;
			}
		}
		
	}
	catch (Exception $e) {
		echo 'Summarize encountered an error: ',  $e->getMessage(), "<br>";
		echo "Error information:<br>";
		var_dump ($e);
	}
?>
	
<!DOCTYPE html>
<html>
<head>
	<title>Summary</title>
	<meta charset="UTF-8"/>
</head>
<body style="background-color: #eee;">
<div style="max-width:700px; margin: 0 auto; font-family:arial; background-color: white; padding: 20px; filter: drop-shadow(0 0 4px #bbb);">
	<h1>Summary</h1>
	<div style="font-size:0.8em;margin:8px 0">
		<a href="<?php echo $u; ?>"><?php echo $u;?></a>
	</div>
	<div  id="main" style="font-size: 1.1em; line-height: 1.4;">
		<?php
			echo ($summary);
		?>
	</div>	
</div>
</body>
</html>
			