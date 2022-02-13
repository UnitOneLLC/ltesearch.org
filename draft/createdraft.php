<?php
	// This is the URL from the app deployment
	$url ="https://script.google.com/macros/s/AKfycbyIG_9PlyZ_zv1QxLItMVA82z18FDcug2T9tZOt4MeBFCklDlBCzKkfRjN_dvJzwtSQ/exec";

	echo file_get_contents($url . "?" . $_SERVER['QUERY_STRING']);
	

// This is the code to use POST instead of GET	
//	$params = [
//		"author" => $_GET["author"],
//		"paper"  =>  $_GET["paper"],
//		"responding_title" => $_GET["responding_title"],
//		"responding_url" => $_GET["responding_url"],
//		"lteaddr" => $_GET["lteaddr"],
//		"title" => $_GET["title"]
//	];
//	
//	$postdata = http_build_query($params);
//
//	$requestHeaders = array(
//		'Content-type: application/x-www-form-urlencoded',
//		sprintf('Content-Length: %d', strlen($postdata))
//	);
//
//
//	$options = array('http' =>
//		array(
//			'method'  => 'POST',
//			'header'  => implode("\r\n", $requestHeaders),
//			'content' => $postdata
//		)
//	);
//
//	$context = stream_context_create($options);
//	echo file_get_contents($url, false, $context);
?>