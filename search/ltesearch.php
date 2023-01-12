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
define('KWSEARCH', 's');
define('GETPAPERDB', 'getpaperdb');
define('DATABASE_FAILURE', 'Database failure');
define('MISSING_PARAM', "A required parameter is missing");
define('REGION_INVALID', "Specified region not configured");
define('MAX_RESULTS', 75);
define('UNKNOWN_TOPIC', 'Unknown topic specified');
define('BAD_FILTER_VALUE', 'Invalid filter argument');
define('HIGHLIGHT_THRESHOLD',4);
define('AUTH_ERROR', 'Authentication error');
define('MIN_RANK', 50);
define('AI_SCREEN_TEMPLATE', 'Would you guess that an article titled "#title" is about any of the following: #subjects? Answer one of the following: Very likely, Maybe, Very unlikely.');

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
		$ttl = trim(str_replace("\xa0", ' ', $ttl));
		$ttl = substr($ttl, 0, strpos($ttl, " - "));
		$ttl = substr($ttl, 0, strpos($ttl, " | "));
		return $ttl;
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
	$filter_strength = $qstr_aa[FILTER];
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

			$kw_search = $qstr_aa[KWSEARCH];
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
	
			$randomized = $keywords;
			shuffle($randomized);
			$terms_string = implode(" ", $randomized);
	
			$apikey = $conn->fetch_api_key('custom_search');
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
				$cse = new CustomSearch($apikey, $engine, $terms_string, $url_filters, $content_filters, $title_filters, $url_suffixes_to_strip);
				$items = $cse->execute_search(MAX_RESULTS, $filter_strength, $is_raw_mode);
				$all_results = array_merge($all_results, $items);
			}

			$all_results = remove_duplicate_urls($all_results);
			
			$papers = $conn->fetch_papers();
			$min_rank = MIN_RANK;
			foreach($all_results as &$result) {
				$n_bold = get_bold_word_count($item["description"]);
				$result["rank"] -= $n_bold*3;
				$result["paper"] = find_paper_name($papers, $result["url"]);
				$result["highlight"] = false; // is_highlight($result);
				$result["zlink"] = encode_url($result["url"]);
				$result["title"] = get_trimmed_title($result["title"]);
				
				if ($result["rank"] < $min_rank) {
					$ft = $result["title"];
					if (endsWith($ft, "...")) {
						$ft = get_full_title($result["url"]);
						if ($ft != null) {
							$result["title"] = $ft;
						}
					}
				}
				
				$result["rank"] -= word_count($result["title"]);
			}

			$screen = build_ai_screen_prompt_template($topic);
			if ($screen != null) {
				$all_results = do_ai_screen($min_rank, $screen, $all_results);
			}
			
			foreach($all_results as &$result) {
				$rank = $result["rank"];
				$result["description"] .= "/r$rank";
			}

			$status = update_queries($conn, $region, $topic, count($all_results), $usertoken);

			$conn = null;
			
			if ($status != "OK") {
			//	echo $status;
			//	return;
			}
			
			echo json_encode($all_results);
		}
		catch (PDOException $e) {
			$conn = null;
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
	
	function do_ai_screen($min_rank, $screen, $results) {
		
		$ret_array = array();
		foreach ($results as $key => $value) {
			if ($value["rank"] > $min_rank) {
// do not return unlikely resutls				array_push($ret_array, $value);
				continue;
			}
			
			$title = $value["title"];
			$instru = str_replace("#title", $title, $screen);
			$postData = array(
				"model" => "text-davinci-003",
				"prompt" => $instru,
				"max_tokens" => 128,
				"temperature" => 0.5
			);
			
			$encoded_postData = json_encode($postData);
			$ai_returned_string = fetch_from_openai($encoded_postData);

			$decoded = json_decode($ai_returned_string);
			if ($decoded && is_array($decoded->choices) && $decoded->choices[0]->text) {
				$answer = $decoded->choices[0]->text;
			}
			else {
				$answer = "Maybe";
			}

			if (strcasecmp(trim($answer), "Very likely") == 0) {
				$rank = $value["rank"] -= 100;
				$value["description"] .= " /s+";
				array_push($ret_array, $value);
			}
			if (strcasecmp(trim($answer), "Maybe") == 0) {
				$rank = $value["rank"];
				$value["description"] .= " /s0";			
				array_push($ret_array, $value);
			}
			else if (strcasecmp(trim($answer), "Very unlikely") == 0) {
				// do not add to return value array
			}
		}
	
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
	
	function get_full_title($url) {
		$html = file_get_contents($url);
		
		$title_regex = '/<title.*>(.*)<\/title>/';
		
		preg_match($title_regex, $html, $title_matches);
		
		if (is_array($title_matches) && (count($title_matches) > 1)) {
			$title = $title_matches[1];
		}
		else {
			$title = null;
		}
		return $title;
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
      $b_filter = ($filter_strength == CustomSearch::FILTER_OFF) ? 0 : 1;
      $pdo->insert_qtab_row($timestamp, $region, $ipaddr, $topic, $n_results, $token);
	  return "OK";
    }
    catch (PDOException $e) {
      return $e->getMessage();
    }
  }
	
	