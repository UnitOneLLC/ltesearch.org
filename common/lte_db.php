<?php
define ("MAX_ARTICLE_CACHE_ROW_COUNT", 100);

class LTE_DB {

	protected $_conn; /* the PDO object */
	
	/*
	 * Get a connection to the lte search database. Throws on failure.
	 */
	function __construct($bWrite=false) {
		if ($bWrite) {
			$dbjson = file_get_contents("../../etc/ltesearch.org/db-write.json");
		}
		else {
			$dbjson = file_get_contents("../../etc/ltesearch.org/db.json");
		}
		$dbarr = json_decode($dbjson, TRUE);
  
		$hostname = $dbarr['hostname'];
		$port = $dbarr['port'];
		$dbname = $dbarr['dbname'];
		$username = $dbarr['username'];
		$password = $dbarr['password'];
		$dbspec = "mysql:dbname=$dbname;host=$hostname;port=$port";
		
		$this->_conn = new PDO($dbspec, $username, $password);
		$this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/*
	 * checks that the given name is a known region and returns the region id. Returns
	 * null if not found.
	 */
	function validate_region($region_name) {
		$stmt = $this->_conn->query("select id from regions where name = '$region_name'");
		$result = $stmt->fetch(PDO::FETCH_NUM);
		$stmt = null;
		return $result[0];
	}
	
	/*
	 * checks that the given topic name is known - returns boolean
	 */
	function validate_topic($topic) {
		$lstr = strtolower($topic);
		$stmt = $this->_conn->query("select region_id from keywords where topic = '$topic'");
		$result = $stmt->fetch(PDO::FETCH_NUM);
		$stmt = null;
		return !empty($result);
	}
	/*
	 * fetch the set of topics
	 */
	function fetch_topics() {
		$stmt = $this->_conn->query("select distinct topic from keywords");
		$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 'topic');
		$stmt = null;
		return $result;
	}
	
	/*
	 * Fetch the set of keywords as an array for the specified topic and region.
	 */
	 function fetch_keywords($topic, $region_id) {
		$stmt = $this->_conn->query("select keyword from keywords where topic='$topic' and (region_id='$region_id' or region_id=0)");
		$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 'keyword');
		$stmt = null;
		return $result;
	 }
	/*
	 * Fetch the set of configured regions.
	 */
	 function fetch_regions() {
		$stmt = $this->_conn->query("select name from regions");
		$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 'name');
		$stmt = null;
		return $result;
	 }
	 
	 /*
	  * Fetch the search engine keys for a specified region
	  */
	 function fetch_engine_keys($region_id) {
	 	$stmt = $this->_conn->query("select gcseid from engines where (region_id='$region_id' or region_id='0')");
	 	$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 'gcseid');
	 	$stmt = null;
	 	return $result;
	 }
	 
	 /*
	  * Fetch the URL filters
	  */
	 function fetch_url_filters($topic, $region_id) {
	 	$stmt = $this->_conn->query("select filter from url_filters where (region_id='$region_id' or region_id=0) and topic='$topic' and remove_suffix=false");
	 	$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 'filter');
	 	$stmt = null;
	 	return $result;
	 }
	/*
	* Fetch the URL filters
	*/
	function fetch_url_removal_suffixes($topic, $region_id) {
		$stmt = $this->_conn->query("select filter from url_filters where (region_id='$region_id' or region_id=0) and topic='$topic' and remove_suffix=true");
		$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 'filter');
		$stmt = null;
		return $result;
	}
	 
	 /*
	  * Fetch the content filters
	  */
	 function fetch_content_filters($topic, $region_id) {
	 	$stmt = $this->_conn->query("select filter from content_filters where (region_id='$region_id' or region_id=0) and topic='$topic'");
	 	$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 'filter');
	 	$stmt = null;
	 	return $result;
	 }
	/*
	 * Fetch the title filters
	 */
	function fetch_title_filters($region_id) {
		$stmt = $this->_conn->query("select blacklisted_title from title_filters where region_id='$region_id' or region_id='0'");
		$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 'blacklisted_title');
		$stmt = null;
		return $result;
	}
	 /*
	  * Fetch papers
	  */
	function fetch_papers() {
		$stmt = $this->_conn->query("select * from papers order by name");
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt = null;
		return $result;
	}
	
	function fetch_paper_by_domain($domain) {
		$sql = "select * from papers where domain like '%$domain'";
		$stmt = $this->_conn->query($sql);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt = null;
		return $result;
	}
	
	function fetch_paper_uses_proxy($domain) {
		try {
			$pdo = $this->_conn;
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// Prepare the SQL query with a placeholder for the domain
			$stmt = $pdo->prepare('SELECT uses_proxy_reader FROM papers WHERE domain = :domain');
			
			// Bind the parameter to the placeholder
			$stmt->bindParam(':domain', $domain, PDO::PARAM_STR);
			
			// Execute the query
			$stmt->execute();
			
			// Fetch the result
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			
			// If a result is found, return the value of "uses_proxy_reader"
			if ($result !== false) {
				return $result['uses_proxy_reader'];
			} else {
				// If no result is found, you may choose to return a default value or handle it as needed
				return null;
			}
			
		} catch (PDOException $e) {
			// Handle database connection or query errors here
			error_log('Error: ' . $e->getMessage());
			return 0;
		}		
	}
	
	/*
	 * add a row to the queries table
	 */
	function insert_qtab_row($timestamp, $region, $ipaddr, $topic, $n_results, $token) {
      $sql = "INSERT INTO queries (timestamp, region, ipaddr, topic, nResults, usertoken) VALUES (?,?,?,?,?,?)";
      $stmt= $this->_conn->prepare($sql);
    
      $stmt->execute([$timestamp, $region, $ipaddr, $topic, $n_results, $token]);
	}
	/*
	 * Get activity
	 */
	function fetch_activity($max_rows) {
		if ($max_rows <= 0) {
			$max_rows = 100;
		}
		$stmt = $this->_conn->query("select * from queries order by timestamp DESC LIMIT $max_rows");
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt = null;
		return $result;
	}
	
	/*
	* Fetch the set of subjects for the ai screen as an array for the specified topic 
	*/
	function fetch_screen_subjects($topic) {
		$stmt = $this->_conn->query("select screen from ai_screen where topic='$topic'");
		$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 'screen');
		$stmt = null;
		return $result;
	}
	/*
	 * fetch the phrase for a subject that is used for talking points and angles
	 */
	function fetch_key_ai_screen_phrase_for_topic($topic) {
		$stmt = $this->_conn->query("select screen from ai_screen where topic='$topic' and is_key_phrase=1");
		$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 'screen');
		$stmt = null;
		if (count($result) === 0) {
			return null;
		}
		return $result[0];
	}
	
	/*
	 * store text in article cache for URL
	 */
	function update_cache_entry($url, $text) {
		error_log("[ENTER update_cache_entry] $url");
		// Convert the HTML content to UTF-8
		try {
			$htmlContent = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
			$timestamp = gmdate("Y-m-d H:i:s");
			$stmt = $this->_conn->prepare("INSERT INTO article_cache (url, timestamp, contents) VALUES (?, ?, ?)");		
			$stmt->execute([$url, $timestamp, $htmlContent]);
			error_log("[update_cache_entry] $url $timestamp");
		} catch (PDOException $e) {
			$errorMessage = $e->getMessage();
			error_log("PDOException: " . $errorMessage);
		}
		catch (Exception $e2) {
			$errorMessage = $e->getMessage();
			error_log("Generic Exception: " . $errorMessage);
		}
	}
	
	/*
	 * retrieve an article from the cache
	 */
	function get_article_from_cache($url) {
		$query = "SELECT contents FROM article_cache WHERE url = ?";
		$stmt = $this->_conn->prepare($query);
		$stmt->execute([$url]);
		
		// Check if the query executed successfully
		if ($stmt !== false) {
			// Check if the query returned any rows
			if ($stmt->rowCount() > 0) {
				// Rows exist, process the results
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					error_log("[CACHE HIT] $url");
					return trim($row['contents']);
				}
			} else {
				error_log("[CACHE MISS] $url");
				return "";
			}
		} else {
			return "";
		}
	}
	
	function trim_cache()
	{
		$safety = 100;
		do {
			$rowCountStmt = $this->_conn->prepare("SELECT COUNT(*) FROM article_cache");
			$rowCountStmt->execute();
			$rowCount = $rowCountStmt->fetchColumn();
error_log("[TRIM_CACHE] current count=$rowCount");
			if ($rowCount > MAX_ARTICLE_CACHE_ROW_COUNT) {
				$findMinTimeStmt = $this->_conn->prepare("SELECT MIN(timestamp) FROM article_cache");
				$findMinTimeStmt->execute();
				$row = $findMinTimeStmt->fetch(PDO::FETCH_ASSOC);
				$minTimestamp = $row['MIN(timestamp)'];

				$deleteStmt = $this->_conn->prepare("DELETE FROM article_cache WHERE timestamp = ?");

				$deleteStmt->execute([$minTimestamp]);
			}
		} while ($rowCount > MAX_ARTICLE_CACHE_ROW_COUNT && --$safety);
		
		return $rowCount;
	}

	
	function get_parameter($p)
	{
		try {
			$pdo = $this->_conn;
			
			// Prepare the SQL statement to fetch the value corresponding to the given parameter
			$sql = "SELECT value FROM parameters WHERE param = :param LIMIT 1";
			$stmt = $pdo->prepare($sql);
			
			// Bind the parameter value to the prepared statement
			$stmt->bindParam(':param', $p, PDO::PARAM_STR);
			
			// Execute the statement
			$stmt->execute();
			
			// Fetch the result as an associative array
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			
			// Check if a value was found or not
			if ($result) {
				return $result['value'];
			} else {
				return null;
			}
		} catch (PDOException $e) {
			// Handle any database connection or query errors here
			// For example, you can log the error or display a user-friendly message
			error_log("Error: " . $e->getMessage());
			return null;
		}
	}
}
?>
