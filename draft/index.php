<?php
	include_once("../common/version.php");
	include_once("../common/urlcode.php");
	include_once("../common/lte_db.php");

	error_reporting(E_ERROR | E_PARSE);
	
	$u = $_GET['u'];
	
	if ($u == null) {
		
		$z = $_GET['z'];
		$u = decode_url($z);
		
		if ($u == null) {
			echo "Missing URL. Nothing to do.\n";
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
	$title=str_replace("https://", "", $title);
	$title=str_replace("http://", "", $title);
	$html = read_html_from_url($u,"Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)");
	$titleOffset = strpos($html, "<title");
	if ($titleOffset !== false) {
		$titleOffset = strpos($html, ">", $titleOffset)+1;
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
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
		<script src="draft.js?v=<?php echo(VERSION);?>"></script>
		<?php
			$topic = $_GET["topic"];
			if ($topic) {
		?>
			<script>var theTopic = '<?php echo($topic); ?>';
			</script>
		<?php
			}
			else { ?>
				<script>var theTopic = 'climate change';
				</script>
		<?php
			}
		?>
		<style type="text/css">
			body {
				background-color: rgba(30,30,120,1);
				font-family: arial;
			}
			#container {
				margin: 30px auto;
				max-width: 900px;
				background-color: white;
				padding: 5px 0 15px 0;
				border-radius: 10px;
			}
			#inner {
				padding: 0 15px;
			}
			#hint {
				font-style: italic;
				width: 50%;
			}
			h1 {
				margin-bottom: 5px;
				font-weight: 200;
				background: linear-gradient(#f0f0f0, #d0d0d0);
				padding: 10px;
				font-size: 1.5em;
			}
			h2 {
				font-size: 1.0em;
				font-weight: 200;
			}
			#copyAndGoBtn {
				vertical-align: top;
				font-size: 1.0em;
				margin: 10px 0 10px 0;
				background-color: rgba(30,30,120,1);
				color: white;
				padding: 3px 7px;
				border-radius: 8px;
			}
			#btn-chg-auth {
				font-size: 0.75em;
				margin-left: 5px;
				padding: 0 2px 2px 2px;
			}
			#spinner-prompt {
				vertical-align: baseline;
			}
			#spinner {
			}
			#hidden-inputs {
				display: none;
			}
			input[type=radio] {
				margin-left: 25px;
			}
			#tp-cell {
				max-height: 180px;
				overflow-y: scroll;
			}
			#tp-table {
				margin: 15px 10px;
				border-top: solid 1px black;
				border-bottom: solid 1px black;
			}
			#btn-get-tp {
				margin: 10px 0;
			}
			#btn-get-angles {
				padding-right: 8px;
				margin: 10px 0 10px 0px;
			}
		</style>
		<script type="text/javascript">
			var g_title = "<?php echo trim($title); ?>";
			var g_url = "<?php echo trim($u); ?>";
			var g_newspaper = "<?php echo trim($paper["name"]) ?>";
			var g_lteaddr = "<?php echo trim($paper["lteaddr"]) ?>";
		</script>
		<title>Create LTE Draft</title>
	</head>
	<body>
		<div id="container">
			<h1>Create a draft LTE Google document</h1>
			<div id="inner">
				<h2>
					<span id="newspaper"> <?php echo $paper["name"]; ?>: </span><a id="hyper" href="<?php echo $u;?>"><?php echo $title?></a><br>
				</h2>
				<div>
					<span id="lte-no-cookie">Enter your name: <input id="author" maxlength=24/></span>
					<span id="lte-have-cookie">Your name: <span id="auth-text"> </span> <button id="btn-chg-auth">Change</button></span>
				</div>
				
				<div>
					<button id="copyAndGoBtn">Create document</button>
					<span id="spinner-prompt">creating doc . . . </span><img id="spinner" height="45px" src="../search/loading_spinner.gif"/>
				</div>
				<div id="hint">
					You will be asked to make a copy of a document. <br>Click the 'Make a copy' button when it appears.<br>
				</div>
			</div>
			<table id="tp-table">
				<tbody><tr>
					<td style="width:15%; vertical-align: top;" colspan="2">
						<div id="tpdiv">
							<div>
								<button id="btn-get-angles">Get suggestions</button>
								<span style="font-style: italic">&nbsp;(uses AI to generate a first draft of your letter)</span>
							</div>
						</div>
					</td>
					<tr>
						<td style="max-height:180px;overflow-y:auto; vertical-align: top;">
							<div id="tp-cell"></div><div id="end-copy"></div>
							<sup><span id="tp-spinner-prompt">Retrieving . . . </span></sup>
							<img id="tp-spinner" height="35px" src="../search/loading_spinner.gif"/>
						</td>
					</tr>
				</tr>
					<tr>
						<td></td>
						<td><button style="float:right" id="btn-copy-ai">Copy</button></td>
					</tr></tbody>
			</table>
		</div>
	</body>
</html>