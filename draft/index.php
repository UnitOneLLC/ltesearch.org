<?php
	include "../common/version.php";
	include "../common/urlcode.php";
	include "../common/lte_db.php";
	
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
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
		<script src="draft.js?v=<?php echo VERSION;?>"></script>
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
				font-weight: 500;
				background: linear-gradient(#eee, #ccc);
				padding: 10px;
			}
			h2 {
				font-size: 1.1em;
				font-weight: 500;
			}
			#copyAndGoBtn {
				vertical-align: top;
				font-size: 1.1em;
				margin: 10px 0 10px 0;
				background-color: rgba(30,30,120,1);
				color: white;
				padding: 2px 5px;
				border-radius: 8px;
			}
			#btn-chg-auth {
				font-size: 0.75em;
				margin-left: 5px;
			}
			#spinner-prompt {
				vertical-align: super;
			}
			#spinner {
			}
			#hidden-inputs {
				display: none;
			}
		</style>
		<script type="text/javascript">
			var g_title = "<?php echo $title; ?>";
			var g_url = "<?php echo $u; ?>";
			var g_newspaper = "<?php echo $paper["name"] ?>";
			var g_lteaddr = "<?php echo $paper["lteaddr"]; ?>";
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
					<span id="no-cookie">Enter your name: <input id="author" maxlength=24/></span>
					<span id="have-cookie">Your name: <span id="auth-text"> </span> <button id="btn-chg-auth">Change</button></span>
				</div>
				
				<div>
					<button id="copyAndGoBtn">Create document</button>
					<span id="spinner-prompt">creating doc . . . </span><img id="spinner" height="45px" src="../search/loading_spinner.gif"/>
				</div>
				<div id="hint">
					You will be asked to make a copy of a document. <br>Click the 'Make a copy' button when it appears.<br>
				</div>
			</div>
		</div>
	</body>
</html>