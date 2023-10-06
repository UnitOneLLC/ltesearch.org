<?php
	include_once("../common/version.php");
	include_once("../common/lte_db.php");
	include_once("../common/urlcode.php");
	
	$using_alternates = false;
	$trace = 0;
	$user_agents = [ #https://developers.whatismybrowser.com/useragents/parse/
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36 Edg/114.0.1823.58",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/113.0",
		"Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36 Edge/18.17763",
		"Googlebot-News",
		"Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)",
		"Mozilla/5.0 (compatible; DotBot/1.1; http://www.opensiteexplorer.org/dotbot, help@moz.com)"
	];
	
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
		global $preFilterDOM;
		
		if ($elem->nodeName == 'article' or ($using_alternates and (($elem->nodeName == 'div') or ($elem->nodeName == 'section')))) {
			$visited = $elem->getAttribute("ltesearch");
			if ($visited == 'true') {
				return;
			}
			else {
				$elem->setAttribute("ltesearch", "true");
			}
		}
		if ($elem->nodeName == "div") {
			$cls = $elem->getAttribute("class");
			if ((stripos($cls, "byline") !== false) || 
				(stripos($cls, "by_") !== false) ||
				(stripos($cls, "authors") !== false)) {
				echo innerHtml($elem);
				return;
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

	$preFilterDOM = function($elem) {
		global $targetHostPrefix;
		if ($elem->nodeName == 'a') {
			$href = $elem->getAttribute("href");
			$url = parse_url($href);
			if (empty($url["host"])) {
				$href = $targetHostPrefix . $href;
				$elem->setAttribute("href", $href);
			}
		}
		else if ($elem->nodeName == 'img') {
			$src = $elem->getAttribute("src");
			$url = parse_url($src);
			if (empty($url["host"])) {
				$src = $targetHostPrefix . $src;
				$elem->setAttribute("src", $src);
			}
		}
		else if ($elem->nodeName == "svg") {
			$elem->parentNode->removeChild($elem);
		}
	};
	
	function dump_meta($doc) {
		global $trace;
		
		if (empty($doc)) return;
		
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
					dbg_trace(2, "&#9;$n=$v");
				}
			}
		}
	}
	# need to handle this syntax:
	#    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	function getCharSet($metas) {
		
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
				else {
					$cs = $node->getAttribute("content");
					if (stripos($cs, "utf-8") !== false) {
						return "utf-8";
					}
				}
			}
		}
		return null;
	}
	
	function getByLine($metas) {
		foreach ($metas as $node) {
			$name = $node->getAttribute("name");
			if (!empty($name) && $name == "byl") {
				$byline = $node->getAttribute("content");
				dbg_trace(1, "byline found in meta=", $byline);
				return $byline;
			}
		}
		return null;
	}
	
	function getPublishDate($metas) {
		global $u;
		
		foreach ($metas as $node) {
			$time_string = "";
			$prop = $node->getAttribute("property");
			$name = $node->getAttribute("name");
			$iprop = $node->getAttribute("itemprop");

			if ($prop == "article:published_time" || 
				$name == "datePublished" ||
				$name == "dateCreated" ||
				$iprop == "dateCreated" ||
				$iprop == "datePublished" 
			) {
				$time_string = $node->getAttribute("content");
				dbg_trace(3, "time string content", $time_string);
				try {
					$time_formatted = date_format(date_create($time_string),"M d, Y");
					dbg_trace(3,"Date found", "|$time_formatted|");
					return $time_formatted;
				} catch (Exception $e) {
					dbg_trace(1,'EXCEPTION in getPublishDate', $e);
					return null;
				}
				break;
			}
		}

		$dt = get_date_from_url($u);
		if ($dt !== false) {
			return strval($dt["month"]) . "/" . strval($dt["day"]) . "/" . strval($dt["year"]);
		}
		
		return null;
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
	
	function hasKnownBodyClass($elem) {
		$known_body_classes = ["lead "];
		$classAttr = $elem->getAttribute("class");
		return contains_any_of($classAttr, $known_body_classes);
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
			if (hasKnownBodyClass($elem)) {
				$looksGood = true;
			}
				
			if ($looksGood) {
				return true;
			}
		}
		return false;
	}
	
	function insertDraftButton() {
		global $u;
		global $host;
		
		try {
			$conn = new LTE_DB();
			$paper = $conn->fetch_paper_by_domain($host);
			$conn = null;
			
			if (!empty($paper["lteaddr"])) {
				$link = "https://ltesearch.org/draft?z=" . encode_url($u);
	?>
				<div style="padding: 30px 0;">
					<a target="_blank" href="<?php echo $link;?>">Create a draft letter</a>
				</div>
	<?php
			}
		}
		catch (PDOException $e) {
			$conn = null;
		}
		
	}
	
		
	function insertImage() {
		global $doc;
		
		if (empty($doc)) return;
		
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
			
			$z = $_GET['z'];
			$u = decode_url($z);
			
			if ($u == null) {
				echo "no URL\n";
				exit(0);
			}
		}
		
		$host = parse_url($u, PHP_URL_HOST);
		if (strncmp($host, "www.", 4) === 0) {
			$host = substr($host, 4);
		}
		dbg_trace(1, "global host set", "$host");
		
		$targetHostPrefix = parse_url($u, PHP_URL_SCHEME) . "://" . parse_url($u, PHP_URL_HOST);
		dbg_trace(1, "target host", $targetHostPrefix);
		
		foreach ($user_agents as $ua) {
			dbg_trace(1, "read with user-agent ", $ua);
			$d = read_html_from_url($u, $ua);
			if (empty($d)) continue;
			dbg_trace(5, "html data", htmlentities($d));
			$doc = new DOMDocument();
			$doc->loadHTML($d);
			$metas = $doc->getElementsByTagName("meta");
			$charset = getCharSet($metas);
			dbg_trace(1, "char set", $charset);			
			$pub_time = getPublishDate($metas);

			dbg_trace(1, "publish time: ", $pub_time);
			$by_line = getByLine($metas);
			
			$need_utf8_decode  = (strcasecmp($charset, "utf-8") !== 0 or $host=='washingtonpost.com');

			$titles = $doc->getElementsByTagName("title");
			if (count($titles) > 0) {
				$title  = $titles->item(0)->textContent;
				if (strcasecmp($charset, "utf-8") !== 0) {	
					$title = utf8_decode($title);
				}
				dbg_trace(1, "title", $title);
				if ((trim($title) !== "Access Denied") && (stripos($title, "are you a robot") == false)) {
					break;
				}
			}
			else 
				$title = null;
		}
		
		if (!empty($doc)) {
			dump_meta($doc);
			
			walkDom($doc, $preFilterDOM);
			
			$articles = $doc->getElementsByTagName("article");
			dbg_trace(1, "article count", strval(count($articles)));
			
			if (empty($articles) or (count($articles) == 0)) {
				$articles = getAlternateArticles($doc);
				$using_alternates = true;
				dbg_trace(1, "using alternates");
			}
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
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script type="text/javascript">
		const FONT_COOKIE_NAME = "reader-font-size";
		function setupFontControl() {
			$("#spinner").hide();
			document.getElementById("btn-font-up").addEventListener("click", ()=>incrFont());
			document.getElementById("btn-font-down").addEventListener("click", ()=>decrFont());
			document.getElementById("btn-copy").addEventListener("click", ()=>copyTextToClipboard());
			document.getElementById("btn-summary").addEventListener("click", ()=>getSummary());
			var fontSize = $.cookie(FONT_COOKIE_NAME);
			if (fontSize) {
				document.getElementById("main").style.fontSize = fontSize.toString() + "em";				
			}
		}
		
		function incrFont() {
			var fs = parseFloat(document.getElementById("main").style.fontSize);
			fs += 0.1;
			document.getElementById("main").style.fontSize = fs.toString() + "em";
			$.cookie(FONT_COOKIE_NAME, fs.toString());
		}
		
		function decrFont() {
			var fs = parseFloat(document.getElementById("main").style.fontSize);
			fs -= 0.1;
			if (fs > 0.2)
				document.getElementById("main").style.fontSize = fs.toString() + "em";
			else fs = 0.3;
			
			$.cookie(FONT_COOKIE_NAME, fs.toString());			
		}
		
		function copyTextToClipboard() {
			// Get the text content of the element with id "main"
			var textToCopy = document.getElementById("main").innerText;
			
			// Create a new ClipboardItem with the text data
			var clipboardItem = new ClipboardItem({ "text/plain": new Blob([textToCopy], { type: "text/plain" }) });
			
			// Access the clipboard and write the ClipboardItem
			navigator.clipboard.write([clipboardItem]).then(
				function () {
					console.log("Text copied to clipboard: " + textToCopy);
				},
				function (err) {
					console.error("Unable to copy text to clipboard", err);
				}
			);
		}
		
		function getSummary() {
			$("#spinner").show();
			$("#btn-summary").prop("disabled", true);
			var text = $("#main").text();
			$.ajax({
				url: "https://ltesearch.org/read/summary.php",
				method: 'POST',
				data: {"text": text},
				success: function(markup) {$("#main").html(markup); $("#spinner").hide();},
				cache: false,
				contentType: false,
				processData: true
			});
		}

	</script>

</head>
<body onload=setupFontControl() style="background-color: #eee;">
<div id="font-control" style="position:fixed; left:5px; top:15px">
	<table><tr>
		<td style="text-align: center;">
			<div>
			<span id="btn-font-up" style="cursor:pointer;padding:0 3px">&#9650;</span>
			</div>
			<div>
				<span id="btn-font-down" style="cursor:pointer;padding:0 3px">&#9660;</span>
			</div>
		</td>
		
		<td>
			<div style="font-family:serif">
				<span style="font-size:1.4em">A</span>
				<span style="font-size:0.7em">A</span>
			</div>
		</td>
	</tr><tr>
		<td  colspan="2">
			<button id="btn-copy">Copy to clipboard</button>
		</td>
	</tr><tr>
		<td  colspan="2">
			<button id="btn-summary">Summarize</button>
			<img id="spinner" style="display:none;padding-left:3px;height:25px;vertical-align:sub" src="loading_spinner.gif">
		</td>
	</tr>
	</table>
</div>
<div style="max-width:700px; margin: 0 auto; font-family:arial; background-color: white; padding: 20px; filter: drop-shadow(0 0 4px #bbb);">
	<h2>
		<?php
			if (!empty($title)) {
				echo $title;
			}
		?>
	</h2>


	<div style="font-size:0.8em;margin:8px 0">
		<a href="<?php echo $u; ?>"><?php echo $u;?></a>
		<div style="margin:8px 0"><?php insertImage(); ?></div>
	</div>
	<?php 
			if (!empty($by_line)) { ?>
				<div style="font-style: italic;"><?php echo $by_line?></div>
			<?php
			}
			if (!empty($pub_time)) { ?>
		<p id="pub-time" style="margin-top: 2px">
			<?php
				echo $pub_time;
			?>
		</p>
	<?php }
	?>
	<div  id="main" style="font-size: 1.1em; line-height: 1.4;">
		<?php
		
		if (!empty($articles) and (count($articles) > 0)) {
			dbg_trace(1, "begin main dom walk");
			foreach($articles as $article) {
				walkDom($article, $visitNode);
			}
			dbg_trace(1, "1st found para count", $found_para_count);
			if ($found_para_count < 5) {
				dbg_trace(1, "walk with paragraph vist");
				foreach($articles as $article) {
					walkDom($article, $paraVisit);
				}
			}
			if ($found_para_count < 5 and !$using_alternates) {
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
			
		

			
		?>
	</div>	
	<div>
		<?php insertDraftButton(); ?>
	</div>
</div>
</body>
</html>
			