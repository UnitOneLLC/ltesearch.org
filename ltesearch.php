<?php
header('Access-Control-Allow-Origin: *');

define('DEFAULT_TOPIC', 'climate');
define('TOPIC', 'topic');
define('FILTER', 'filter');
define('ACTION', 'action');
define('REGION', 'region');
define('SEARCH', 'search');
define('MODE', 'mode');
define('RAW', 'raw');
define('GETPAPERDB', 'getpaperdb');
define('DATABASE_FAILURE', 'Database failure');
define('MISSING_PARAM', "A required parameter is missing");
define('REGION_INVALID', "Specified region not configured");
define('MAX_RESULTS', 200);
define('UNKNOWN_TOPIC', 'Unknown topic specified');
define('BAD_FILTER_VALUE', 'Invalid filter argument');
define('HIGHLIGHT_THRESHOLD',4);

include "CustomSearch.php";
include "lte_db.php";

	function return_error($error, $data) {
		return '{"error": "' . $error . ': ' . $data . '"}';
	}
	
	function find_paper_name($papers, $url) {
		foreach($papers as $paper) {
			if (strpos($url, trim($paper["domain"])) !== false) {
				return $paper["name"];
			}
		}
		return parse_url($url, PHP_URL_HOST);
	}


	function result_compare($r1, $r2) {
		return strcmp($r1["url"], $r2["url"]);
	}	
	function remove_duplicate_urls($results) {
		if (count($results) == 0) return;
		
		$sorted = $results;
		$results = array();
		usort($sorted, "result_compare");

		# combine descriptions if URLs match
		array_push($results, $sorted[0]);
		for ($i=1; $i < count($sorted); ++$i) {
			if (strcmp($sorted[$i]["url"],$sorted[$i-1]["url"]) != 0) {
				array_push($results, $sorted[$i]);
			}
			else {
				$results[count($results)-1]["description"] .= $sorted[$i]["description"];
			}
		}
		
		return $results;		
	}
	
	function get_bold_word_count($str) 
	{
		return substr_count($str, "<b>");
	}
	
	function is_highlight($item) {
		$n_bold = get_bold_word_count($item["description"]);
		if ($n_bold >= HIGHLIGHT_THRESHOLD) {
			return "true";
		}
		else {
			return "false";
		}
	}
		
	# begin script

	$parts = parse_url($_SERVER['REQUEST_URI']);
	parse_str($parts['query'], $qstr_aa);

	$action = $qstr_aa[ACTION];
	if (empty($action)) {
		echo return_error(MISSING_PARAM, ACTION);
	}

	if ($action == GETPAPERDB) {
		try {
			$conn = new LTE_DB();
			$papers = $conn->fetch_papers();
			$conn = null;
			echo json_encode($papers);
		}
		catch (PDOException $e) {
			$conn = null;
		}
		return;
	}

	$topic = $qstr_aa[TOPIC];
	if (empty($topic)) {
		$topic = DEFAULT_TOPIC;
	}
	else {
		try {
			$conn = new LTE_DB();
			if (!$conn->validate_topic($topic)) {
				echo return_error(UNKNOWN_TOPIC, $topic);
				return;
			}
			$conn = null;
		}
		catch (PDOException $e) {
			$conn = null;
		}
	}
	$filter_strength = $qstr_aa[FILTER];
	if (empty($filter_strength)) {
		$filter_strength = CustomSearch.FILTER_STRONG;
	}
	else {
		if ($filter_strength == 'off') {
			$filter_stength = CustomSearch.FILTER_OFF;
		}
		else if ($filter_strength == 'weak') {
			$filter_strength = CustomSearch.FILTER_WEAK;
		}
		else if ($filter_strength == 'strong') {
			$filter_strength = CustomSearch.FILTER_STRONG;
		}
		else {
			echo return_error(BAD_FILTER_VALUE, $filter_strength);
			return;
		}
	}
	
	if ($action == SEARCH) {
		
		$is_raw_mode = false;
		$mode = $qstr_aa[MODE];
		if (!empty($mode)) {
			$is_raw_mode = ($mode == RAW);
		}
		
		$region = $qstr_aa[REGION];
		if (empty($region)) {
			echo return_error(MISSING_PARAM, REGION);
			return;
		}
		
		try {
			$conn = new LTE_DB();
			$region_id = $conn->validate_region($region);
			if (empty($region_id)) {
				echo return_error(MISSING_PARAM, REGION);
				return;
			}

			$keywords = $conn->fetch_keywords($topic, $region_id);		
			foreach($keywords as &$kw) {
				if (strpos($kw, ' ') !== false) {
					$kw = '"' . $kw . '"';
				}
			}
	
			$randomized = $keywords;
			shuffle($randomized);
	
			$terms_string = implode(" ", $randomized);
	
			$apikey = $conn->fetch_api_key('custom_search');
			$engines = $conn->fetch_engine_keys($region_id);
			$url_filters = $conn->fetch_url_filters($topic, $region_id);
			$content_filters = $conn->fetch_content_filters($topic, $region_id);
			$title_filters = $conn->fetch_title_filters($region_id);

			if (empty($engines)) {
				return return_error(REGION_INVALID, $region);
			}
			
			$all_results = array();
			foreach ($engines as $engine) {
				$cse = new CustomSearch($apikey, $engine, $terms_string, $url_filters, $content_filters, $title_filters);
				$items = $cse->execute_search(MAX_RESULTS, $filter_strength, $is_raw_mode);
				$all_results = array_merge($all_results, $items);
			}

			$all_results = remove_duplicate_urls($all_results);
			
			$papers = $conn->fetch_papers(); 
			foreach($all_results as &$result) {
				$result["paper"] = find_paper_name($papers, $result["url"]);
				$result["highlight"] = is_highlight($result);
			}

			$conn = null;
			
			echo json_encode($all_results);
		}
		catch (PDOException $e) {
			$conn = null;
			echo return_error(DATABASE_FAILURE, $e->getMessage());
			return;
		}
	}
	
