<?php
	include "../common/lte_db.php";

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
	}

	echo file_get_contents($url . "?" . $_SERVER['QUERY_STRING']);
?>