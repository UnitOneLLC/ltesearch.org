<?php
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
	
	function read_html_from_url($url) {
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:95.0) Gecko/20100101 Firefox/95.0");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	

?>