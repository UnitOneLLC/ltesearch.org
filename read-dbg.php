<?php
	include "lte_db.php";
	
	error_reporting(E_ERROR | E_PARSE);
		
	function walkDom($elem, $visit) {
if (get_class($elem) == 'DOMElement') {
	$tag = $elem->nodeName;
	$cls = $elem->getAttribute("class");
	$id = $elem->getAttribute("id");
	echo "DOM Walk: tag=$tag class=$cls id=$id <br>";
}
		$visit($elem);
		foreach ($elem->childNodes as $child) {
			walkDom($child, $visit);
		}
	}
	
	$found_para_count = 0;
	
	$visitNode = function ($elem) {
		global $found_para_count;
		global $relativeUrlFix;
		if (looksLikeArticleBody($elem)) {
			$text = "";
			$child = $elem->firstChild;
			do {
				if ($child->nodeName == 'p') {
					$text = $text . "<p>" . innerHtml($child) . "</p>";
					$found_para_count++;
				}
				$child = $child->nextSibling;
			} while ($child != null);
			
			echo $text;
			return true;
		}
	
		return false;
	};
	
	$paraVisit = function($elem) {
		global $found_para_count;
		global $relativeUrlFix;
		if ($elem->nodeName == 'p') {
			echo "<p>" . innerHtml($elem) . "</p>";
			$found_para_count++;
		}
	};

	$relativeUrlFix = function($elem) {
		global $targetHostPrefix;

		if ($elem->nodeName == 'a') {
			$href = $elem->getAttribute("href");
			$url = parse_url($href);
			if (empty($url["host"])) {
				$href = $targetHostPrefix . $href;
				$elem->setAttribute("href", $href);
			}
		}
	};
	
	function getCharSet($doc) {
		$metas = $doc->getElementsByTagName("meta");
		foreach ($metas as $node) {
			$cs = $node->getAttribute("charset");
			if (!empty($cs)) {
				return $cs;
			}
			else {
				$cs = $node->getAttribute("charSet");
				if (!empty($cs)) {
					return $cs;
				}
			}
		}
		return null;
	}

	function contains_any_of($str, $needles) {
		if (empty($str))
			return false;
		foreach ($needles as $needle) {
			if (strpos($str, $needle) !== false) {
				return true;
			}
		}
		return false;
	}
	
	function getAlternateArticles($doc) {
		$articles = array();
		$divs = $doc->getElementsByTagName("div");
		foreach ($divs as $div) {
			$cls = $div->getAttribute("class");
			if (contains_any_of($cls, ["article", "body", "content"])) {
				echo "Alternate article has clas $cls<br>";
				array_push($articles, $div);
			}
		}
		return $articles;
	}
	
	function innerHtml($p) {
		global $charset;
		$html = $p->ownerDocument->saveHTML($p);
		if (strcasecmp($charset, "utf-8") !== 0) {
			$html = utf8_decode($html);
		}
		return $html;
	}
	
	function looksLikeArticleBody($elem) {
		$looksGood;
		if (get_class($elem) == "DOMElement")		{
			$cls = $elem->getAttribute("class");
			$nChildren = count($elem->childNodes);
			echo "Testing element $elem->nodeName class=$cls child count=$nChildren<br>";
		}
		foreach ($elem->childNodes as $child) {
			if ($child->nodeName == "#text") {
				continue;
			}
			
			if ($child->nodeName == 'p') {
echo "Found child para<br>";
				$thisNode = $child;
				$looksGood = true;
				$limit = 2;
				for ($i=0; $i < $limit; ++$i) {
					if (($thisNode) and ($thisNode->nextSibling->nodeName == 'p')) {
echo "Found para sibling para count=$i<br>";
						$thisNode = $thisNode->nextSibling;
					}
					else if (($thisNode) and ($thisNode->nextSibling->nodeName == '#text')) {
						$thisNode = $thisNode->nextSibling;
						$limit++;
					}
					else {
						$looksGood = false;
						break;
					}
				}
			}
			if ($looksGood) {
				return true;
			}
		}
		return false;
	}
	
	function insertSubmitAddress() {
		global $u;
		$host = parse_url($u, PHP_URL_HOST);
		if (strncmp($host, "www.", 4) === 0) {
			$host = substr($host, 4);
		}

		try {
			$conn = new LTE_DB();
			$paper = $conn->fetch_paper_by_domain($host);
			$conn = null;
			
			if (!empty($paper["lteaddr"])) {
				$lteaddr = $paper["lteaddr"];
				if (strncmp($lteaddr, "http", 4) !== 0) { # form, build link
					$link = "mailto:" . $lteaddr;
				}
				else {
					$link = $lteaddr;
				}
	?>
				<div style="padding: 30px 0; font-weight:bold; font-family:arial">
					Submit letters to <a href="<?php echo $link;?>"><?php echo $lteaddr?></a>
				</div>
	<?php
			}
		}
		catch (PDOException $e) {
			$conn = null;
		}
		
	}
	
	function read_html_from_url($url) {
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:95.0) Gecko/20100101 Firefox/95.0");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
		
	function insertImage() {
		global $doc;
		
		$image = null;
		
		$metas = $doc->getElementsByTagName("meta");
		foreach ($metas as $node) {
			$name = $node->getAttribute("name");
			if (!empty($name) and ($name == "twitter:image")) {
				$image = $node->getAttribute("content");
				break;
			}
		}
		if (!empty($image)) {
			echo "<img src='$image'/>";
		}

	}
	
	
#===================================================
	try {	
		$u = $_GET['u'];
		
		if ($u == null) {
			echo "no URL\n";
			exit(0);
		}
		
		$targetHostPrefix = parse_url($u, PHP_URL_SCHEME) . "://" . parse_url($u, PHP_URL_HOST);
#		$d = file_get_contents($u);
		$d = read_html_from_url($u);
		$doc = new DOMDocument();
		$doc->loadHTML($d);
		$charset = getCharSet($doc);
		
		$titles = $doc->getElementsByTagName("title");
		if (count($titles) > 0) {
			$title  = $titles->item(0)->textContent;
			if (strcasecmp($charset, "utf-8") !== 0) {
				$title = utf8_decode($title);
			}
		}
		else 
			$title = null;
		
		walkDom($doc, $relativeUrlFix);
		
		$articles = $doc->getElementsByTagName("article");
		echo "Count of article elements is " + count($articles) + "<br>";
		$using_alternates = false;
		if (empty($articles) or (count($articles) == 0)) {
			echo "Using alternates<br>";
			$articles = getAlternateArticles($doc);
			$using_alternates = true;
		}
		echo "The article count is " . count($articles) . "<br>";
	}
	catch (Exception $e) {
		echo 'Reader encountered an error: ',  $e->getMessage(), "<br>";
		echo "Error information:<br>";
		var_dump ($e);
	}
?>
	
<!DOCTYPE html>
<html>
<head>
	<title>
		<?php
			if (!empty($title)) {
				echo $title;
			}
		?>
	</title>
</head>
<body style="max-width:700px; margin: 0 auto; font-family:arial; font-size: 20px; line-height: 1.4;">
	<h2>
		<?php
			if (!empty($title)) {
				echo $title;
			}
		?>
	</h2>
	<div>
		<a href="<?php echo $u; ?>"><?php echo "(" . $u . ")";?></a>
		<div><?php insertImage(); ?></div>
	</div>
	<div >
		<?php
		
		if (count($articles) > 0) {
			foreach($articles as $article) {
				walkDom($article, $visitNode);
			}
			if ($found_para_count < 3) {
				foreach($articles as $article) {
					walkDom($article, $paraVisit);
				}
			}
			if ($found_para_count < 3 and !$using_alternates) {
				$articles = getAlternateArticles($doc);
				foreach($articles as $article) {
					walkDom($article, $paraVisit);
				}
			}
		}
		else {
			echo "No article found.";
			exit(0);
		}
			
		
		insertSubmitAddress();
			
		?>
	</div>		
</body>
</html>
			