<?php
define(SECONDS_PER_DAY, 60*60*24);

class CustomSearch {
	const HOST_URL = 'https://content.googleapis.com/customsearch/v1';
	const MAX_ITEMS = 10;
	const FILTER_STRONG = 'strong';
	const FILTER_WEAK = 'weak';
	const FILTER_OFF = 'off';
	const HIGHLIGHT_THRESHOLD = 4;
	
	protected $_engine_id;
	protected $_search_terms;
	protected $_url_filters;
	protected $_content_filters;
	protected $_title_filters;
	protected $_apikey;
	protected $_terms_array;
	
	function explode_term_list($term_list) {
		$DELIM = "^";
		$strx = '';
		$in_quotes = false;
		for ($i=0; $i < strlen($term_list); ++$i) {
			$ch = $term_list[$i];
			if ($ch === '"') {
				$in_quotes = !$in_quotes;
				continue;
			}
			if (!$in_quotes and ($ch === ' ')) {
				$strx .= $DELIM;
			}
			else {
				$strx .= $ch;
			}

		}
		return explode($DELIM, $strx);
	}

	function __construct($apikey, $engine, $search_terms, $url_filters, $content_filters, $title_filters) {
		$this->_engine_id = $engine;
		$this->_search_terms  = $search_terms;
		$this->_terms_array = self::explode_term_list($search_terms);
		$this->_url_filters = $url_filters;
		$this->_content_filters = $content_filters;
		$this->_title_filters = $title_filters;
		$this->_apikey = $apikey;
	}

	function buildQuery($number, $start_index) {
		$q = self::HOST_URL;
		$q .= '?cx=' . rawurlencode($this->_engine_id);
		$q .= '&key=' . rawurlencode($this->_apikey);
		$q .= '&dateRestrict=d2';
		$q .= '&lr=lang_en';
		$q .= "&num=$number";
		$q .= "&start=$start_index";
		$q .= '&q=' . rawurlencode("");
		$q .= '&orTerms=' . rawurlencode($this->_search_terms);
/*		$q .= '&excludeTerms=' . rawurlencode($this->_excludes); */
		return $q;
	}
	
	function zero_pad_left($s) {
		$ival = intval($s);
		if ($ival == 0)
			return $s;
		if ($ival >= 10)
			return $s;
		return '0'.$s;
	}
	
	function use_numeric_month($s) {
		$ival = intval($s);
		if ($ival != 0) {
			return self::zero_pad_left($s);
		}
		$s = substr(strtolower($s),0,3);
		$months = array("jan"=>"01","feb"=>"02","mar"=>"03","apr"=>"04","may"=>"05","jun"=>"06",
		                "jul"=>"07","aug"=>"08","sep"=>"09","oct"=>"10","nov"=>"11","dec"=>"12");
		return $months[$s];
	}

	
	function execute_search($max_items, $filter_strength, $is_raw_mode) {
		$current_index = 1;
		$ret_arr = array();
		$watchdog = 0;
		
		do {
			$watchdog++;
			if ($watchdog > 25) {
				echo "WATCHDOG FAIL";
				break;
			}
			$q = $this->buildQuery(self::MAX_ITEMS, $current_index);
			$curlObj = curl_init();
			curl_setopt($curlObj, CURLOPT_URL, $q);
			curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($curlObj, CURLOPT_HTTPGET, TRUE);

			$json = curl_exec($curlObj);

			$result_aa = json_decode($json, TRUE);
			$items = $result_aa["items"];
			if (is_null($items)) {
				break;
			}
			
			if ($is_raw_mode) {
				$current_index += count($items);
				$ret_arr = array_merge($ret_arr, $items);
				if (count($ret_arr) == $max_items) 
					break;
					
				continue;
			}

			$now = time();
			
			foreach($items as $item) {
				$date_aa = self::estimate_item_date($item);
				
				/* ignore items older than two days */
				$date_str = $date_aa['month'].'/'.$date_aa['day']."/".$date_aa['year'];
				$elapsed_days = ($now - strtotime($date_str))/SECONDS_PER_DAY;
				if (($elapsed_days > 2) or ($elapsed_days < 0)) {
					continue;
				}
				
				if (($filter_strength != CustomSearch.FILTER_OFF) and !$this->filter_url($item["link"])) {
					continue;
				}
				
				if (($filter_strength != CustomSearch.FILTER_OFF) and !$this->filter_contents($item["snippet"] . " " . $item['title'], $item["htmlSnippet"], $filter_strength)) {
					continue;
				}

				if (($filter_strength != CustomSearch.FILTER_OFF) and !$this->filter_titles($item['title'])) {
					continue;
				}
				
				if (strlen(parse_url($item["link"], PHP_URL_PATH)) <= 1) {
					continue;
				}
				
				$ret_item = array(
					"pubDate" => $date_aa['year'].'-'. $date_aa['month'].'-'. $date_aa['day'],      
					"url" => $item["link"], 
					"title" => $item["title"],
					"description" => $item["htmlSnippet"],
					"highlight" => self::is_highlight($item));
					
				array_push($ret_arr, $ret_item);
				
				if (count($ret_arr) == $max_items) 
					break;
			}
			$current_index += count($items);
		} while ($current_index < $max_items);

		return $ret_arr;
	}
	
	function get_bold_word_count($str) 
	{
		$doc = new DOMDocument();
		$doc->loadHtml($str);
		$bolds = $doc->getElementsByTagName('b');
		
		$bcount = $bolds->length;
		$all_bold = "";
		
		for ($i=0; $i < $bcount; ++$i) {
			$item = $bolds->item($i);
			$all_bold .= $item->nodeValue . " ";
		}
		
		$arr = preg_split('/\s+/', trim($all_bold));
		return count($arr);	
	}
	
	function is_highlight($item) {
		$n_bold = self::get_bold_word_count($item["htmlSnippet"]);
		if ($n_bold >= self::HIGHLIGHT_THRESHOLD) {
			return "true";
		}
		else {
			return "false";
		}
	}
	
	function filter_url($url) {
		$passes = true;
		$lurl = strtolower($url);
		foreach ($this->_url_filters as &$filter) {
			if (strpos($lurl, $filter) !== false) {
				$passes = false;
				break;
			}
		}
		return $passes;
	}

	function looks_like_clickbait($s) {
		$words = preg_split("/[\s,]+/", $s);
		$first_chars = array();
		$count_upper = 0;
		$count_lower = 0;

		foreach($words as $word) {
			$char = substr($word, 0, 1);
			if (ctype_upper($char)) ++$count_upper;
			else if (ctype_lower($char)) ++$count_lower;	
		}

		$is_bait = (($count_lower < 2) or 
			(($count_lower > 0) and ($count_upper/$count_lower) >= 3));
	
		return $is_bait;
	}
	
	function filter_contents($str, $htmlStr, $filter_strength) {
		$passes = true;
		$lstr = strtolower($str);
		
		if ($filter_strength == CustomSearch.FILTER_STRONG) {
			if (strpos($htmlStr, '<b>') === false) {
				$keyword_found = false;
				foreach ($this->_terms_array as $t) {
					if (strpos($str, $t) !== false) {
						$keyword_found = true;
						break;
					}
				}
				
				if (!$keyword_found) {
					return false;
				}
			}
		}
		
		foreach ($this->_content_filters as &$filter) {
			if (strpos($lstr, $filter) !== false) {
				$passes = false;
				break;
			}
		}
		
		if ($passes and ($filter_strength == CustomSearch.FILTER_STRONG)) {
			$passes = ! self::looks_like_clickbait($str);
		}
		return $passes;
	}
	
	function filter_titles($title) {
		$passes = true;
		
		foreach ($this->_title_filters as &$t) {
			if (strpos($title, $t) !== false) {
				$passes = false;
				break;
			}
		}
		
		return $passes;
	}
	
	function try_date($aa, $tag, &$date) {
		if (array_key_exists($tag, $aa)) {
			$date = date_parse($aa[$tag]);
			$date["day"] = self::zero_pad_left($date["day"]);
			$date["month"] = self::use_numeric_month($date["month"]);
			return true;
		}
		else
			return false;
	}
		
	function estimate_item_date($item) {
		if (!array_key_exists("pagemap", $item)) {
			$dt = getdate();
			$dt["day"] = $dt["mday"];
			$dt["day"] = self::zero_pad_left($dt["day"]);
			$dt["month"] = self::use_numeric_month($dt["month"]);			
			return $dt;
		}
			
		$pagemap = $item["pagemap"];
		if (array_key_exists("article", $pagemap)) {
			$article = $pagemap["article"][0];
			if (self::try_date($article, "datepublished", $date))
				return $date;
			if (self::try_date($article, "datecreated", $date))
					return $date;
			if (self::try_date($article, "datemodified", $date))
				return $date;
		} 
		if (array_key_exists("metatags", $pagemap)) {
			$metatags = $pagemap["metatags"][0];
			
			if (self::try_date($metatags, "eomportal-lastupdate", $date))
				return $date;
			if (self::try_date($metatags, "bt:pubdate", $date))
				return $date;
			if (self::try_date($metatags, "pubdate", $date))
				return $date;
			if (self::try_date($metatags, "article_date_original", $date))
				return $date;
			if (self::try_date($metatags, "article:published_time", $date))
				return $date;
		}
		if (array_key_exists("newsarticle", $pagemap)) {
			$newsarticle = $pagemap["newsarticle"][0];
			
			if (self::try_date($newsarticle, "datepublished", $date)) {
				return $date;
			}
		}
		if (array_key_exists("document", $pagemap)) {
			$newsarticle = $pagemap["document"][0];
			
			if (self::try_date($newsarticle, "article_date_original", $date))
				return $date;
		}

		$dt = getdate();
		$dt["day"] = $dt["mday"];
		$dt["day"] = self::zero_pad_left($dt["day"]);
		$dt["month"] = self::use_numeric_month($dt["month"]);			
		return $dt;
	}
}
