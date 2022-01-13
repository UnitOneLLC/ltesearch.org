<?php
	include "lte_db.php";
	
	$using_alternates = false;
	$trace = 0;
	
	function walkDom($elem, $visit) {
		global $trace;
		if ($trace >= 5) {
			if (get_class($elem) == 'DOMElement') {
				$tag = $elem->nodeName;
				$cls = $elem->getAttribute("class");
				$id = $elem->getAttribute("id");
				dbg_trace(5, "DOM Walk", "tag=$tag class=$cls id=$id");
			}
		}
		$visit($elem);
		foreach ($elem->childNodes as $child) {
			walkDom($child, $visit);
		}
	}
	
	$found_para_count = 0;
	
	$visitNode = function ($elem) {
		global $found_para_count;
		global $using_alternates;
		global $relativeUrlFix;
		
		if ($elem->nodeName == 'article' or ($using_alternates and ($elem->nodeName == 'div'))) {
			$visited = $elem->getAttribute("ltesearch");
			if ($visited == 'true') {
				return;
			}
			else {
				$elem->setAttribute("ltesearch", "true");
			}
		}
		
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

		if ($elem->nodeName == 'p') {
			echo "<p>" . innerHtml($elem) . "</p>";
			$found_para_count++;
		}
	};

	$relativeUrlFix = function($elem) {
		global $targetHostPrefix;
#echo "rUF node name is '$elem->nodeName' <br>";
		if ($elem->nodeName == 'a') {
			$href = $elem->getAttribute("href");
			$url = parse_url($href);
			if (empty($url["host"])) {
				$href = $targetHostPrefix . $href;
				$elem->setAttribute("href", $href);
			}
		}
	};
	
	function dump_meta($doc) {
		global $trace;
		dbg_trace(2, "enter dump meta");
		if ($trace >= 2) {
			$metas = $doc->getElementsByTagName("meta");
			dbg_trace(2, "meta count", strval(count($metas)));
			foreach ($metas as $meta) {
				$attrs = $meta->attributes;
				dbg_trace(2, "--meta");
				foreach ($attrs as $attr) {
					$n = $attr->name;
					$v = $attr->nodeValue;
					dbg_trace(2, "attr", "name=$n value=$v");
				}
			}
		}
	}
	
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
				array_push($articles, $div);
			}
		}
		return $articles;
	}
	
	function innerHtml($p) {
		global $charset;
		global $need_utf8_decode;
		
		$html = $p->ownerDocument->saveHTML($p);
		dbg_trace(4, "html before utf-8 decode", $html);
		if ($need_utf8_decode) {
			$html = utf8_decode($html);
			dbg_trace(4, "html after utf-8 decode", $html);			
		}
		else {
			dbg_trace(4, "not decoding");
		}
		return $html;
	}
	
	function looksLikeArticleBody($elem) {
		global $trace;
		$looksGood;

		if ($trace >= 3) {
			if (get_class($elem) == "DOMElement")		{
				$cls = $elem->getAttribute("class");
				$nChildren = count($elem->childNodes);
				dbg_trace(3, "Testing element", "$elem->nodeName class=$cls child count=$nChildren");
			}
		}
	
		foreach ($elem->childNodes as $child) {
			if ($child->nodeName == "#text") {
				continue;
			}
			
			if ($child->nodeName == 'p') {
				dbg_trace(3, "found child para");
				$thisNode = $child;
				$looksGood = true;
				$limit = 2;
				for ($i=0; $i < $limit; ++$i) {
					if (($thisNode) and ($thisNode->nextSibling->nodeName == 'p')) {
						dbg_trace(3, "Found para sibling para",  "count=$i");
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
		global $host;
		
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
			echo "<img src='$image' style='max-height:300px'/>";
		}
		
	}
		
	function dbg_trace($level, $str, $val='') {
		global $trace;
		
		if ($trace >= $level) {
			echo "$str";
			if (!empty($val)) {
				echo ": $val";
			}
			echo "<br>";
		}
	}
	
#===================================================
	try {	
		$trace_param = $_GET['trace'];
		if (!empty($trace_param)) {
			$trace = intval($trace_param);
		}
		
		if ($trace < 5) {
			error_reporting(E_ERROR | E_PARSE);
		}
		
		$u = $_GET['u'];
		
		if ($u == null) {
			echo "no URL\n";
			exit(0);
		}
		
		$host = parse_url($u, PHP_URL_HOST);
		if (strncmp($host, "www.", 4) === 0) {
			$host = substr($host, 4);
		}
		dbg_trace(1, "global host set", "$host");
		
		
		
		$targetHostPrefix = parse_url($u, PHP_URL_SCHEME) . "://" . parse_url($u, PHP_URL_HOST);
		dbg_trace(1, "target host", $targetHostPrefix);
#		$d = file_get_contents($u);
		$d = read_html_from_url($u);
		dbg_trace(5, "html data", $d);
		$doc = new DOMDocument();
		$doc->loadHTML($d);
		$charset = getCharSet($doc);
		dbg_trace(1, "char set", $charset);
		
		$need_utf8_decode  = (strcasecmp($charset, "utf-8") !== 0 or $host=='washingtonpost.com');
		
		dump_meta($doc);
		
		$titles = $doc->getElementsByTagName("title");
		if (count($titles) > 0) {
			$title  = $titles->item(0)->textContent;
			if (strcasecmp($charset, "utf-8") !== 0) {
				$title = utf8_decode($title);
			}
			dbg_trace(1, "title", $title);
		}
		else 
			$title = null;
		
		walkDom($doc, $relativeUrlFix);
		
		$articles = $doc->getElementsByTagName("article");
		dbg_trace(1, "article count", strval(count($articles)));

		if (empty($articles) or (count($articles) == 0)) {
			$articles = getAlternateArticles($doc);
			$using_alternates = true;
			dbg_trace(1, "using alternates");
		}
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
	<meta charset="UTF-8"/>
	<script type="text/javascript">
		function setupFontControl() {
			document.getElementById("btn-font-up").addEventListener("click", ()=>incrFont());
			document.getElementById("btn-font-down").addEventListener("click", ()=>decrFont());        
		}
		
		function incrFont() {
			var fs = parseFloat(document.getElementById("main").style.fontSize);
			fs += 0.1;
			document.getElementById("main").style.fontSize = fs.toString() + "em";
		}
		
		function decrFont() {
			var fs = parseFloat(document.getElementById("main").style.fontSize);
			fs -= 0.1;
			if (fs > 0.2)
				document.getElementById("main").style.fontSize = fs.toString() + "em";
		}
	</script>

</head>
<body onload=setupFontControl() >
<div id="font-control" style="position:fixed; left:30px; top:30px">
	<table><tr>
		<td>
			<div>
			<span id="btn-font-up" style="cursor:pointer;padding:0 3px">&#9650;</span>
			</div>
			<div>
				<span id="btn-font-down" style="cursor:pointer;padding:0 3px">&#9660;</span>
			</div>
		</td>
		
		<td>
			<div style="font-family:serif">
				<span style="font-size:1.5em">A</span>
				<span style="font-size:0.8em">A</span>
			</div>
		</td>
	</tr></table>
</div>
<div style="max-width:700px; margin: 0 auto; font-family:arial;">
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
	<div  id="main" style="font-size: 1.1em; line-height: 1.4;">
		<?php
		
		if (count($articles) > 0) {
			dbg_trace(1, "begin main dom walk");
			foreach($articles as $article) {
				walkDom($article, $visitNode);
			}
			dbg_trace(1, "1st found para count", $found_para_count);
			if ($found_para_count < 3) {
				dbg_trace(1, "walk with paragraph vist");
				foreach($articles as $article) {
					walkDom($article, $paraVisit);
				}
			}
			if ($found_para_count < 3 and !$using_alternates) {
				dbg_trace(1, "walk alternates with paragraph vist");				
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
</div>
</body>
</html>
			