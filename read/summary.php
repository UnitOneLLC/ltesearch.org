<?php
	include_once "../common/version.php";
	include_once "../common/urlcode.php";
	include_once "../common/lte_db.php";
	include_once "../common/aiutility.php";

	define("PROMPT", 'Compose a brief summary of the following article. Give bullet points for the main points, each starting on a new line. Format the output as HTML. Here is the article: ');

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
	$form_vars = get_form_variables();
	
	$text = read_from_summary_cache($form_vars["article_url"]);
	if (!empty($text)) {
		echo $text;
	}
	else {
		$text = $form_vars["text"];
		$summary = query_ai(PROMPT . $text);
		cache_summary($form_vars["article_url"], $summary);
		echo $summary;
	}

?>