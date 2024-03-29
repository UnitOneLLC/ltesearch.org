<?php
	include_once("../common/lte_db.php");


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
		$url = $conn->get_parameter("draft_webapp");
	}
	catch (PDOException $e) {
		$conn = null;
		error_log("PDOException in create draft: " . $e->getMessage());
	}
	$uri = $url . "?" . $_SERVER['QUERY_STRING'];
	echo fetch($uri);
?>