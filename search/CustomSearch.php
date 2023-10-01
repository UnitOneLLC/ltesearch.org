<?php
	
define("SECONDS_PER_DAY", 60*60*24);

function dbg($label, $var) {
	echo $label . ": ";
	var_dump($var);
	echo "<br>";
}

class CustomSearch {
	const HOST_URL = 'https://content.googleapis.com/customsearch/v1';
	const MAX_ITEMS = 10;
	const FILTER_STRONG = 'strong';
	const FILTER_WEAK = 'weak';
	const FILTER_OFF = 'off';
	const MAX_TERMS_LENGTH = 100;
	
	protected $_engine_id;
	protected $_search_terms;
	protected $_url_filters;
	protected $_content_filters;
	protected $_title_filters;
	protected $_apikey;
	protected $_terms_groups;
	
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

	function __construct($apikey, $engine, $search_terms, $url_filters, $content_filters, $title_filters, $url_suffixes_to_strip, $debug) {
		$this->_engine_id = $engine;
		$this->_search_terms  = $search_terms;
		$this->_terms_array = self::explode_term_list($search_terms);
		$this->_terms_groups = self::break_terms($this->_terms_array);
		$this->_url_filters = $url_filters;
		$this->_content_filters = $content_filters;
		$this->_title_filters = $title_filters;
		$this->_apikey = $apikey;
		$this->suffixes = $url_suffixes_to_strip;
		$this->debug = $debug;
	}

	function build_query($number, $start_index, $orterms) {
		$q = self::HOST_URL;
		$q .= '?cx=' . rawurlencode($this->_engine_id);
		$q .= '&key=' . rawurlencode($this->_apikey);
		$q .= '&dateRestrict=d2';
		$q .= '&lr=lang_en';
		$q .= "&num=$number";
		$q .= "&start=$start_index";
		$q .= '&q=' . rawurlencode("");
		$q .= '&orTerms=' . rawurlencode(implode(" ",$orterms));
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
	
	function break_terms($terms) {
		$result = array();

		$length = 0;
		$group = array();
		foreach($terms as $t) {
			if (($length + strlen($t)) > self::MAX_TERMS_LENGTH) {
				array_push($result, $group);
				$group = array();
				$length = 0;
			}
			else {
				$length += strlen($t) + 1;
			}
			
			if (strpos($t, ' ') !== false) {
				$t = "\"" . $t . "\"";
			}
			
			array_push($group, $t);
		}
		if (count($group) > 0)
			array_push($result, $group);

		return $result;
	}

	
	function execute_search($max_items, $filter_strength, $is_raw_mode) {
		$ret_arr = array();

		for ($it=0; $it < count($this->_terms_groups); ++$it) {
			$cur_group = $this->_terms_groups[$it];
			$current_index = 1;
			$watchdog = 0;
		
			do {
				$watchdog++;
				if ($watchdog > 25) {
					echo "WATCHDOG FAIL";
					break;
				}

				$q = $this->build_query(self::MAX_ITEMS, $current_index, $cur_group);
				$curlObj = curl_init();
				curl_setopt($curlObj, CURLOPT_URL, $q);
				curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($curlObj, CURLOPT_HTTPGET, TRUE);
				$json = curl_exec($curlObj);
				/*dbg("JSON", $json);*/
				curl_close($curlObj);
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
				date_default_timezone_set('America/New_York');
				$counter = -1;
				if ($this->debug > 1) {
					self::dump_raw_items($items);
				}
				foreach($items as $item) {
					$counter += 1;
					$date_aa = self::estimate_item_date($item);
					
					/* ignore items older than two days */
					$date_str = $date_aa['month'].'/'.$date_aa['day']."/".$date_aa['year'];
					$elapsed_days = ($now - strtotime($date_str))/SECONDS_PER_DAY;
					if (($elapsed_days > 2) or ($elapsed_days < 0)) {
						continue;
					}
					
					if ($filter_strength != self::FILTER_OFF) {
						$url = $item["link"];
						foreach ($this->suffixes as $suf) {
							$ending = substr($url,  strlen($url) - strlen($suf));
							if (strcmp($ending, $suf) == 0) {
								$item["link"] = substr($url, 0, strlen($url) - strlen($suf));
								break;
							}
						}
					}
					
					if (($filter_strength != self::FILTER_OFF) and !$this->filter_url($item["link"], $this->debug)) {
						continue;
					}
					
					if (($filter_strength != self::FILTER_OFF) and !$this->filter_contents($item["snippet"] . " " . $item['title'], $item["htmlSnippet"], $filter_strength, $this->debug)) {
						continue;
					}

					if (($filter_strength != self::FILTER_OFF) and !$this->filter_titles($item['title'], $this->debug)) {
						continue;
					}
					
					if (strlen(parse_url($item["link"], PHP_URL_PATH)) <= 1) {
						continue;
					}
					
					$descr = str_replace("<b>...</b>", "...", $item["htmlSnippet"]);
					
					$ret_item = array(
						"pubDate" => $date_aa['year'].'-'. $date_aa['month'].'-'. $date_aa['day'],      
						"url" => $item["link"], 
						"title" => $item["title"],
						"rank" => $current_index + $counter,
						"description" => $descr);
						
					array_push($ret_arr, $ret_item);
					
					if (count($ret_arr) == $max_items) 
						break;
				}
				$current_index += count($items);
			} while (count($ret_arr) < $max_items);
		}

		return $ret_arr;
	}

	function filter_url($url, $debug) {
		$passes = true;
		$lurl = strtolower($url);
		foreach ($this->_url_filters as &$filter) {
			if (strpos($lurl, $filter) !== false) {
				$passes = false;
				if ($debug != 0) {
					error_log("[FILTER][URL] $url");
				}
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
	
	function filter_contents($str, $htmlStr, $filter_strength, $debug) {
		$passes = true;
		$lstr = strtolower($str);
		
		$htmlStr = str_replace("<b>...</b>", "...", $htmlStr);
		
		if ($filter_strength == self::FILTER_STRONG) {
			if (strpos($htmlStr, '<b>') === false) {
				$keyword_found = false;
				foreach ($this->_terms_array as $t) {
					if (strpos($str, $t) !== false) {
						$keyword_found = true;
						break;
					}
				}
				
				if (!$keyword_found) {
					if ($debug != 0) {
						error_log("[FILTER][CONTENTS-1] $str");
					}
					return false;
				}
			}
		}
		
		foreach ($this->_content_filters as &$filter) {
			if (strpos($lstr, $filter) !== false) {
				if ($debug != 0) {
					error_log("[FILTER][CONTENTS-2] $str");
				}
				$passes = false;
				break;
			}
		}
		
		if ($passes and ($filter_strength == self::FILTER_STRONG)) {
			$passes = ! self::looks_like_clickbait($str);
		}
		return $passes;
	}
	
	function looks_like_rollup($s) {
		$pattern = "/^([a-zA-Z]+)\s+\|\s+[A-Za-z]+/";
		if (preg_match($pattern, $s) == 1) {
			return true;
		}
		$pattern = "/^([a-zA-Z]+)\s+\–\s+[A-Za-z]+/";
		if (preg_match($pattern, $s) == 1) {
			return true;
		}
	}
	
	function filter_titles($title, $debug) {
		if ($this->looks_like_rollup($title)) {
			if ($debug != 0) {
				error_log("[FILTER][ROLLUP] $title");
			}
			return false;
		}
		
		$passes = true;
		
		foreach ($this->_title_filters as &$t) {
			if (strpos($title, $t) !== false) {
				if ($debug != 0) {
					error_log("[FILTER][TITLE] $title");
				}
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
	
	function get_date_from_url($surl) {
		$reg_ex = "/\/(20\d\d)\/(\d\d)\/(\d\d)\//";
		if (preg_match($reg_ex, $surl, $matches) > 0) {
			$dt = array();
			$dt["day"] = $matches[3];
			$dt["month"] = $matches[2];
			$dt["year"] = $matches[1];
			return $dt;
		}
		else {
			return false;
		}
	}
	
	function dump_raw_items($items) {
		foreach ($items as $item) {
			error_log("[RAW] "  . $item["link"] . " " . $item["title"]);
		}
	}
		
	function estimate_item_date($item) {
		if (!array_key_exists("pagemap", $item)) {
			$dt = self::get_date_from_url($item["link"]);
			if ($dt === false) {
				$dt = getdate();
				$dt["day"] = $dt["mday"];
				$dt["day"] = self::zero_pad_left($dt["day"]);
				$dt["month"] = self::use_numeric_month($dt["month"]);			
			}
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

		$dt = self::get_date_from_url($item["link"]);
		if ($dt === false) {
			date_default_timezone_set('America/New_York');
			$dt = getdate();
			$dt["day"] = $dt["mday"];
			$dt["day"] = self::zero_pad_left($dt["day"]);
			$dt["month"] = self::use_numeric_month($dt["month"]);
		}
		return $dt;
	}
}
