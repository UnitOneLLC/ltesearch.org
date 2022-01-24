<?php

class LTE_DB {

	protected $_conn; /* the PDO object */
	
	/*
	 * Get a connection to the lte search database. Throws on failure.
	 */
	function __construct() {
		$dbjson = file_get_contents("../etc/ltesearch.org/db.json");
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
	 * Fetch the api key for the named api, using the provided db connection.
	 */
	function fetch_api_key($apiname) {
	    $stmt = $this->_conn->query("select apikey from api where name = '$apiname'");
	    $result = $stmt->fetch(PDO::FETCH_ASSOC);
	    $stmt = null;
	    return $result['apikey'];
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
}
?>
