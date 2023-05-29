<?php
	include "../common/lte_db.php";


	function fetch($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:97.0) Gecko/20100101 Firefox/97.0");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: text/plain']);
		$result = curl_exec($ch);
		return $result;
	}

	$url = "";
	try {
		$conn = new LTE_DB();
		$urls = $conn->get_draft_webapp_url();
		$conn = null;
		
		foreach($urls as $u) {
			$url = $u;
			break;
		}
	}
	catch (PDOException $e) {
		$conn = null;
		echo("exception "); var_dump($e);
	}
	$uri = $url . "?" . $_SERVER['QUERY_STRING'];
//	echo $uri;
	error_log("the url is " .$uri);
	echo fetch($url . "?" . $_SERVER['QUERY_STRING']);// . "|" . $url;
?>