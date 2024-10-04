<?php
header('Access-Control-Allow-Origin: *');

define('DEFAULT_TOPIC', 'climate');
define('TOPIC', 'topic');
define('USERTOKEN', 'tkn');
define('FILTER', 'filter');
define('ACTION', 'action');
define('REGION', 'region');
define('SEARCH', 'search');
define('MODE', 'mode');
define('RAW', 'raw');
define('LTEDEBUG','debug');
define('KWSEARCH', 's');
define('GETPAPERDB', 'getpaperdb');
define('DATABASE_FAILURE', 'Database failure');
define('MISSING_PARAM', "A required parameter is missing");
define('REGION_INVALID', "Specified region not configured");
define('MAX_RESULTS', 40);
define('UNKNOWN_TOPIC', 'Unknown topic specified');
define('BAD_FILTER_VALUE', 'Invalid filter argument');
define('HIGHLIGHT_THRESHOLD',4);
define('AUTH_ERROR', 'Authentication error');
//define('AI_SCREEN_TEMPLATE', 'Would you guess that the subject matter of a news article entitled "#title" is related to any of the following: #subjects? Answer one of the following: Very likely, Maybe, Very unlikely.');

//define('AI_SCREEN_TEMPLATE', 'On a scale of 1 to 100, where 1 means very unlikely, how likely is a news article entitled "#title" to be related to one of the following subjects: #subjects ? Do not give your reasoning. If the title is probably for a press release, the rank is 1. Divide score by 2 if the articles ia about a country other than the United States. Your answer must always be a single number, the maximum score over all the given subjects.');

define('AI_SCREEN_TEMPLATE', 'I will give you a JSON array. Each element in the array is an associative array. Your job is to return the full array after adding to each element a new key-value pair. The key of the new key-value pair is "s". The value of "s" is an integer. You compute the value of "s" based on the value of the "title" key in the same associative array. Assign to the "s" value the probability from 0 to 100 that a newspaper article whose title is given in the "title" value is related to any of the following subjects: #subjects. Return ONLY the updated JSON array without any additional preface, explanation or comment. Here is the JSON array:');


define('MIN_RANK', 10);
define('AI_CUTOFF', 30);
define('MAX_SCORE', 200);

include "CustomSearch.php";
include_once "../common/lte_db.php";
include_once "../common/urlcode.php";
include_once "../common/aiutility.php";

	function return_error($error, $data) {
		return '{"error": "' . $error . ': ' . $data . '"}';
	}
	
	function find_paper_name($papers, $url) {
		$matchlen = 0;
		$matched = parse_url($url, PHP_URL_HOST);
		
		foreach($papers as $paper) {
			$trimmed = trim($paper["domain"]);
			if (strpos($url, $trimmed) !== false) {
				if (strlen($trimmed) > $matchlen) {
					$matchlen = strlen($trimmed);
					$matched = $paper["name"];
				}
			}
		}
		return $matched;
	}


	function result_compare($r1, $r2) {
		return strcmp($r1["url"], $r2["url"]);
	}
	function remove_duplicate_urls($results) {
		if (count($results) == 0) return array();
		
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

	function word_count($string) {
		$words = preg_split('/[\t\s\-]+/', $string);
		return count($words);
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
	
	function get_form_variables() {
		$result = array();
		$post_args = explode("&", file_get_contents('php://input'));
		for ($i=0; $i < count($post_args); $i++) {
			$arg_pair = explode("=", $post_args[$i]);
			if (count($arg_pair) == 2) {
				$result[$arg_pair[0]] = $arg_pair[1];
			}
		}
		return $result;
	}

	function trim_title($ttl) {
		$ch = " - ";
		if (strpos($ttl, $ch) === false) { 
			$sub = $ttl; 
		} else {
			$sub = substr($ttl, 0, strpos($ttl, $ch)); 
		}
		$ttl = trim(str_replace("&#160;", ' ', $sub));
		$ttl = substr($ttl, 0, strpos($ttl, " - "));
		$ttl = substr($ttl, 0, strpos($ttl, " | "));
		return $ttl;
	}
	
	# begin script

	$parts = parse_url($_SERVER['REQUEST_URI']);
	parse_str($parts['query'], $qstr_aa);

	$action = @$qstr_aa[ACTION];
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
			return_error(DATABASE_FAILURE, "papers");
		}
		finally {
			$conn = null;
		}
		return;
	}
	

	$form_vars = get_form_variables();

	$usertoken = $form_vars["tkn"];
	$usertoken = urldecode($usertoken);


	try {
		$user_email = base64_decode($usertoken);
		if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
			echo return_error(AUTH_ERROR, $user_email);
			return;
		}
	}
	catch (Exception $e) {
		echo return_error(AUTH_ERROR, "");
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
			echo return_error(DATABASE_FAILURE, "topic");
			return;
		}
	}
	$filter_strength = @$qstr_aa[FILTER];
	if (empty($filter_strength)) {
		$filter_strength = CustomSearch::FILTER_STRONG;
	}
	else {
		if ($filter_strength == 'off') {
			$filter_strength = CustomSearch::FILTER_OFF;
		}
		else if ($filter_strength == 'weak') {
			$filter_strength = CustomSearch::FILTER_WEAK;
		}
		else if ($filter_strength == 'strong') {
			$filter_strength = CustomSearch::FILTER_STRONG;
		}
		else {
			echo return_error(BAD_FILTER_VALUE, $filter_strength);
			return;
		}
	}
	
	if ($action == SEARCH) {
		
		$is_raw_mode = false;
		$mode = @$qstr_aa[MODE];
		if (!empty($mode)) {
			$is_raw_mode = ($mode == RAW);
		}
		
		$region = $qstr_aa[REGION];
		if (empty($region)) {
			echo return_error(MISSING_PARAM, REGION);
			return;
		}
		
		$debug = @$qstr_aa[LTEDEBUG];
		
		try {
			$conn = new LTE_DB();
			$region_id = $conn->validate_region($region);
			if (empty($region_id)) {
				echo return_error(MISSING_PARAM, REGION);
				return;
			}

			$kw_search = @$qstr_aa[KWSEARCH];
			$keywords = array();
			if (empty($kw_search)) {
				$keywords = $conn->fetch_keywords($topic, $region_id);
				foreach($keywords as &$kw) {
					if (strpos($kw, ' ') !== false) {
						$kw = '"' . $kw . '"';
					}
				}
			}
			else {
				$keywords[0] = '"' . $kw_search . '"';
			}
	
//			$randomized = $keywords;
//			shuffle($randomized);
			$terms_string = implode(" ", $keywords);
	
			$apikey = $conn->get_parameter("custom_search_api_key");
			$engines = $conn->fetch_engine_keys($region_id);
			$url_filters = $conn->fetch_url_filters($topic, $region_id);
			$url_suffixes_to_strip = $conn->fetch_url_removal_suffixes($topic, $region_id);
			$content_filters = $conn->fetch_content_filters($topic, $region_id);
			$title_filters = $conn->fetch_title_filters($region_id);

			if (empty($engines)) {
				return return_error(REGION_INVALID, $region);
			}
			
			$all_results = array();
			foreach ($engines as $engine) {
				$cse = new CustomSearch($apikey, $engine, $terms_string, $url_filters, $content_filters, $title_filters, $url_suffixes_to_strip, $debug);
				$items = $cse->execute_search(MAX_RESULTS, $filter_strength, $is_raw_mode);
				$all_results = array_merge($all_results, $items);
			}

			$all_results = remove_duplicate_urls($all_results);
			
			$papers = $conn->fetch_papers();
			$conn = null;

			foreach($all_results as &$result) {
				$n_bold = get_bold_word_count($result["description"]);
				$result["rank"] -= $n_bold*3;
				$result["paper"] = find_paper_name($papers, $result["url"]);
				$result["highlight"] = false; // is_highlight($result);
				$result["zlink"] = encode_url($result["url"]);
				$result["title"] = get_trimmed_title($result["title"]);
				
				if ($result["rank"] > MIN_RANK) {
					$ft = $result["title"];
					if (endsWith($ft, "...") and strlen($ft) < 100) {
						$ft = get_full_title($result["url"]);
						if ($ft != null) {
							$result["title"] = $ft;
						}
					}
				}
				
				$title_word_count = word_count($result["title"]); 
				if ($title_word_count == 1){
					$result["rank"] += 300;					
				}
				else if ($title_word_count == 2) {
					$result["rank"] += 90;
				}
				$result["rank"] -= $title_word_count*2;
			}

			$screen = build_ai_screen_prompt_template($topic);
			if ($screen != null) {
				$all_results = screen_results($keywords, MIN_RANK, $screen, $all_results, $debug);
			}
			
			foreach($all_results as &$result) {
				$rank = $result["rank"];
				$result["description"] .= "/r$rank";
			}
			
			// Sorting the array in descending order based on the numeric value of 'rank'
			usort($all_results, function ($a, $b) {
				return intval($b['rank']) <=> intval($a['rank']);
			});			

			$conn = new LTE_DB();
			
			$max_search_items = $conn->get_parameter('max_search_items');
			if (count($all_results) > $max_search_items) {
				$all_results = array_slice($all_results, 0, $max_search_items);
			}
			
			$status = update_queries($conn, $region, $topic, count($all_results), $usertoken);

			$conn = null;
			
			if ($status != "OK") {
				error_log("update_queries failed with status $status");
			}
			
			echo json_encode($all_results);
		}
		catch (PDOException $e) {
			$conn = null;
			error_log("Exception: " . $e->getMessage());
			echo return_error(DATABASE_FAILURE, $e->getMessage());
			return;
		}
	}
	
	function build_ai_screen_prompt_template($topic) {
		try {
			$conn = new LTE_DB();
			$subjects = $conn->fetch_screen_subjects($topic);
			if (count($subjects) === 0) {
				return null;
			}
			$subj_list = implode(",", $subjects);
			$prompt = str_replace("#subjects", $subj_list, AI_SCREEN_TEMPLATE);
			$conn = null;			
			return $prompt;
		}
		catch (PDOException $e) {
			$conn = null;			
			return null;
		}
	}
	
	function containsKeyword($title, $keywords) {
		foreach ($keywords as $keyword) {
			if (stripos($title, $keyword) !== false) {
				// Case-insensitive check for keyword in title
				return true;
			}
		}
		return false;
	}
	
	function ai_screen_results($keywords, $min_rank, $screen, $results, $debug) {
		$chunk_size = 20;
		$n_to_scan = count($results);
		$n_processed = 0;
		$result_array = array();
		
		while ($n_to_scan > 0) {
			$n_screen_this_chunk = min($chunk_size, $n_to_scan);

			$this_chunk = array();
			for ($i=0; $i < $n_screen_this_chunk; ++$i) {
				array_push($this_chunk, $results[$n_processed+$i]);
			}
			$this_chunk = ai_screen_results_chunk($keywords, $min_rank, $screen, $this_chunk, $debug);
			$result_array = array_merge($result_array, $this_chunk);
			
			$n_processed += $n_screen_this_chunk;
			$n_to_scan -= $n_screen_this_chunk;
		}

		return $result_array;
	}
		
	function ai_screen_results_chunk($keywords, $min_rank, $screen, $results, $debug) {
		$titles_only = array();
		foreach ($results as $result) {
			array_push($titles_only, ["t"=> $result["title"], "s"=> 0]);
		}
		
		$json_results = json_encode($titles_only);
		$screen .= $json_results;
		$ai_result_string = trim(query_ai($screen));

		// Find the position of the first '['
		$start_pos = strpos($ai_result_string, '[');
		
		// Find the position of the last ']'
		$end_pos = strrpos($ai_result_string, ']');
		
		// Check if both '[' and ']' exist in the string
		if ($start_pos !== false && $end_pos !== false && $end_pos > $start_pos) {
			// Extract the part of the string between '[' and ']'
			$ai_result_string = substr($ai_result_string, $start_pos, $end_pos - $start_pos + 1);
		} else {
			// If the brackets are not found or invalid, return the original string or handle it as needed
			$ai_result_string = $input_string;
		}

		$ai_result_array = json_decode($ai_result_string, true);

		// Loop through each element in $ai_result_array
		foreach ($ai_result_array as $ai_result) {
			// Extract the 't' and 's' values from $ai_result
			$ai_title = $ai_result['t'];
			$ai_score = $ai_result['s'];
			
			// Loop through $results to find a matching 'title'
			foreach ($results as &$result) { // Use reference to modify the $results array
				if ($result['title'] === $ai_title) {
					// When a match is found, add the 'ai_score' key with the value of 's'
					$result['ai_score'] = $ai_score;
					break; // Exit the inner loop once the match is found
				}
			}
		}		
		
		$ret_array = array();
		foreach ($results as &$air) {
			$score = intval($air["ai_score"]);
			if ($score >= AI_CUTOFF) {
				$air["rank"] = (int)$air["rank"] - $score;
				$air["description"] .= " /s" . $score;

				array_push($ret_array, $air);
			}
			else if ($debug != 0) {
				error_log("[FILTER][AI] $score] " . $air["title"] . " (" . $air["url"] . ")");
			}
		}

		return $ret_array;
	}
	

	function screen_results($keywords, $min_rank, $screen, $results, $debug) {
		
		$ret_array = array();
		$titles_ai_screened = array();
		
		foreach ($results as $key => $value) {
			$url = $value["url"];
			$title = $value["title"];
			
			if ($value["rank"] >= MAX_SCORE) {
				continue;
			}
			
			if ((stripos($title, "letter") !== false) || (stripos($url, "letter") !== false)) {
//				$value["rank"] = 51;
				$value["description"] .= " /s" . "51";
				array_push($ret_array, $value);
			}
			else if (containsKeyword($title, $keywords)) {
//				$value["rank"] = 90;
				$value["description"] .= " /s" . "90";
				array_push($ret_array, $value);
			}
			else {
//				$value["rank"] = -1;
				array_push($titles_ai_screened, $value);
			}
		}
		
		$screened = ai_screen_results($keywords, $min_rank, $screen, $titles_ai_screened, $debug);
			
		$ret_array = array_merge($ret_array, $screened);
				
	
		return $ret_array;
	}

	function endsWith($string, $ending) {
		$endingLength = strlen($ending);
		if ($endingLength > strlen($string)) {
			return false;
		}
		$substring = substr($string, -$endingLength);
		return $substring === $ending;
	}

	function get_title_from_url($url) { // ChatGPT
		if (!endsWith($url, "/")) {
			$url = $url . "/";
		}
		
		// Define the regular expression pattern
		$pattern = '#\/([a-zA-Z0-9]+(?:-[a-zA-Z0-9]+){2,})\/#';
		
		// Use preg_match to find the first match
		if (preg_match($pattern, $url, $matches)) {
			// Extract the matched sequence
			$result = $matches[1];
			
			// Replace hyphens with spaces
			$result = str_replace('-', ' ', $result);
			
			// Remove forward slashes
			$result = str_replace('/', '', $result);
			
			// Remove trailing numeric characters
			$result = rtrim($result, '0..9');
			
			// Ensure the result string ends with an alphabetic character
			$result = rtrim($result, 'a..zA..Z');
			
			return $result;
		} else {
			// Return an empty string if no match is found
			return '';
		}
	}	
	
	function get_full_title($url) {
		$title = get_title_from_url($url);
		
		if (empty($title)) {
			return null;
		}
		return ucwords($title);
	}
	
	function get_ip_address() {
    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
        if (array_key_exists($key, $_SERVER) === true){
            foreach (explode(',', $_SERVER[$key]) as $ip){
                $ip = trim($ip); // just to be safe

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                    return $ip;
                }
            }
        }
    }
  }
  
  function update_queries($pdo, $region, $topic, $n_results, $token) {
    try {
      $timestamp = gmdate("Y-m-d H:i:s");
      $ipaddr = get_ip_address();
      $pdo->insert_qtab_row($timestamp, $region, $ipaddr, $topic, $n_results, $token);
	  return "OK";
    }
    catch (PDOException $e) {
      return $e->getMessage();
    }
  }
	
	