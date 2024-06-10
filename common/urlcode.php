<?php
	include_once("lte_db.php");
	
	function encode_url($url) {
		$crc = crc32($url);
		$crchex = dechex($crc);
		$len = strlen($crchex);
		for ($i=0; $i < 8-$len; ++$i) {
			$crchex = '0' . $crchex;
		}
		$b64 = base64_encode($url);
		
		return urlencode($crchex . $b64);
	}
	
	function decode_url($str) {
		$crchex = substr($str, 0, 8);
		$b64enc = substr($str, 8);
		$b64 = urldecode($b64enc);
		$url = base64_decode($b64);
		$crc = hexdec($crchex);
		
		if ($crc = crc32($url)) {
			return $url;
		}
		else return null;
	}
	
	function read_from_cache($url) {
		$article = "";
		try {
			$conn = new LTE_DB();
			$article = $conn->get_article_from_cache($url);
			$article = trim($article);
			$conn = null;
		}
		catch (PDOException $e) {
			$conn = null;
		}
		finally {
			$conn = null;
		}
		return $article;
	}

	function paper_uses_proxy_reader($u) {
		$host = parse_url($u, PHP_URL_HOST);
		if (strncmp($host, "www.", 4) === 0) {
			$host = substr($host, 4);
		}
		
		$conn = new LTE_DB();
		$is_proxied = $conn->fetch_paper_uses_proxy($host);
		$conn = null;

		return $is_proxied;
	}
	
	function cache_article($url, $text) {
		if (strlen($text) < 1000) // likely an error page
			return;
		
		try {
			//error_log("[CACHE URL] $url");
			$conn = new LTE_DB();
			$article = $conn->update_cache_entry($url, $text);
			$conn->trim_cache();
			$conn = null;
		}
		catch (PDOException $e) {
			$conn = null;
		}
		finally {
			$conn = null;
		}
	}
	
	function read_with_proxy($url, $agent) {
		$encoded_url = urlencode($url);
		$encoded_agent = urlencode($agent);
		try {
			$conn = new LTE_DB();
			$proxy_url = $conn->get_parameter("proxy_webapp");
		}
		catch (PDOException $e) {
			$conn = null;
			echo("exception "); var_dump($e);
			return;
		}
		
		$final_url = "$proxy_url?url=$encoded_url&user_agent=$encoded_agent";
		return file_get_contents($final_url);
	}
	
	function read_html_from_url($url, $ua="") {
		
		$data = read_from_cache($url);

		if (strlen($data) > 0) {
			return $data;
		}
		
		if (empty($ua)) {
			$ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36";
		}
		
		$is_proxied = paper_uses_proxy_reader($url);
		if ($is_proxied) {
			$data = read_with_proxy($url, $ua);
			cache_article($url, $data);
			return $data;
		}
		
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $ua);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_REFERER, "twitter.com");
		$data = curl_exec($ch);
		curl_close($ch);
		
		cache_article($url, $data);
		
		return $data;
	}
	# other user-agent
	#"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:95.0) Gecko/20100101 Firefox/95.0"
	#also see: https://developers.whatismybrowser.com/useragents/parse/

	function get_trimmed_title($str) {
		$pos1 = strpos($str, " - ");
		$pos2 = strpos($str, " | ");
		
		if ($pos1 === false && $pos2 === false) {
			return $str;
		}
		
		$pos = ($pos1 !== false) ? $pos1 : $pos2;
		return trim(substr($str, 0, $pos));
	}	
		
?>