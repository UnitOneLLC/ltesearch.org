<?php
	include "version.php";
	include "urlcode.php";
	include "lte_db.php";
	
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
	
	$conn = new LTE_DB();
	$paper = $conn->fetch_paper_by_domain($host);
	$conn = null;
	if (empty($paper)) {
		$paper = ["name"=>"unknown", "lteaddr"=>"unknown"];
	}
	
	
	$title = $u;
	$html = read_html_from_url($u);
	$titleOffset = strpos($html, "<title>");
	if ($titleOffset !== false) {
		$titleOffset += strlen("<title>");
		$titleEnd = strpos($html, "</title>");
		if ($titleEnd !== false) {
			$title = substr($html, $titleOffset, $titleEnd-$titleOffset);
		}
	}
	
	
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="robots" content="noindex,nofollow">
		<meta name="google-signin-client_id" content="139675035932-s3i8081v6er03o89aes68ujirk1b99d6.apps.googleusercontent.com">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script src="draft.js?v=<?php echo VERSION;?>"></script>
		<style type="text/css">
			body {
				max-width: 800px;
				margin: 30px auto;
			}
			#hint {
				margin-left: 20px; 
				font-style: italic;
				display: inline-block;
				width: 60%;
			}
			#copyAndGoBtn {
				vertical-align: top;
			}
		</style>
	</head>
	<body>
<div id="startCopy">
</div>

<div id="header-markup" style="font-size:14.5px">
Status<span style="font-size:8pt">&nbsp (DRAFT or SUBMITTED)</span>:&ensp;DRAFT<br>
In response to:&nbsp;<a id="hyper" href="<?php echo $u;?>"><?php echo $title?></a><br>
Newspaper:&ensp;<span id="newspaper"> <?php echo $paper["name"]; ?></span><br>
Date:&ensp;<span id="date"></span><br><br>
Author:&ensp;<span id="auth_container"><input id="author"><span id="auth_text"></span></span><br>
Editors:&ensp;<br>
Submit to:&ensp;<a id="submit_anchor"><span id="submit_addr"><?php echo $paper["lteaddr"];?></span></a><br><br>
<span style="background-color: #1a73e8; color:white"><b>&nbsp;Share&nbsp;</b></span><span style="color:white">.</span>your draft in <u>comment</u> mode to your LTE Google Group.<hr><br><br>
To the editor:
<div><br></div> 
</div>

<div id="endCopy">
</div>
		<div>
			<button id="copyAndGoBtn">Copy header and create Google doc</button>
			<div id="hint">
				When the Google doc opens, you can click on the blank document and then paste in the header above.<br>
				How to paste: press ^V (Windows) or &#8984;-V (Mac).
			</div>
		</div>
	</body>
</html>