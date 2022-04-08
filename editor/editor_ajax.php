<?php
	define('ACTION', 'action');
	define('URL', 'url');
	
	include "../common/version.php";
	include "../common/lte_db.php";
	include "../common/urlcode.php";

	error_reporting(E_ERROR | E_PARSE);
	
	$u = $_GET[URL];
	$action = $_GET[ACTION];
	
	if ($action == "lookup") {
		$host = parse_url($u, PHP_URL_HOST);
		if (strncmp($host, "www.", 4) === 0) {
			$host = substr($host, 4);
		}
		
		$conn = new LTE_DB();
		$paper = $conn->fetch_paper_by_domain($host);
		$conn = null;
		if (empty($paper)) {
			$paper = ["name"=>"unknown", "lteaddr"=>"unknown"];
		}
		
		$title = $u;
		$title=str_replace("https://", "", $title);
		$title=str_replace("http://", "", $title);
		$html = read_html_from_url($u,"Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)");
		$titleOffset = strpos($html, "<title");
		if ($titleOffset !== false) {
			$titleOffset = strpos($html, ">", $titleOffset)+1;
			$titleEnd = strpos($html, "</title>");
			if ($titleEnd !== false) {
				$title = substr($html, $titleOffset, $titleEnd-$titleOffset);
			}
		}
		$encoded = encode_url($u);
		
		$result = ["paper" => $paper["name"], "title" => $title, "zlink" => $encoded];
		
		echo json_encode($result);
	}
	else {
		echo ("error: unknown request");
	}
?>