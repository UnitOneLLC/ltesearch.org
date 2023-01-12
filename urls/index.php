<?php
include "../common/version.php";
include "../common/urlcode.php";
include "../common/lte_db.php";
  
?>
<!DOCTYPE html>
<head>
    <meta name="robots" content="noindex,nofollow">
    <title>LTE Get URL</title>

    <style type="text/css">
        body {
           background-color: rgba(30,30,120,1);
        }
        #container {
            margin: auto;
            background-color: #e8e8f0;
            padding: 10px;
            overflow-wrap: break-word;
        }
        #input_url {
            width: 60%;
        }
        
        @media  (min-width: 600px) {
        	#container {
				max-width: 900px;
				width: 66%;
        	}
        }
        @media  (min-width: 1000px) {
        	#container {
				max-width: 1100px;
				width: 75%;
        	}
        }
    </style>
    <script src="urls.js?v=<?php echo VERSION;?>"></script>
</head>
<?php 
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>        
<body>
    <div id="container">
        <form method="POST">
            <label for="input_url">Enter URL:</label>&nbsp;
            <input type="text" id="input_url" name="input_url" value="">
            <input type="submit" value="Submit">
        </form>
    </div>
</body>

<?php } else if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $valid_url = true;
    try {    
      $url = $_POST["input_url"];
      $parsed = parse_url($url);
      
      if (empty($url) or  ($parsed["scheme"] != "http" and $parsed["scheme"] != "https") or empty($parsed["host"])) {
        throw new Exception("bogus url");
      }
      
      $encoded = encode_url($url);
      $text_url = "https://ltesearch.org/read?z=" . $encoded;
      $draft_url = "https://ltesearch.org/draft?z=" . $encoded;
      
      $title = $url;
      $title=str_replace("https://", "", $title);
      $title=str_replace("http://", "", $title);
      
      $html = read_html_from_url($url);
      $titleOffset = strpos($html, "<title");
      if ($titleOffset !== false) {
        $titleOffset = strpos($html, ">", $titleOffset)+1;
        $titleEnd = strpos($html, "</title>");
        if ($titleEnd !== false) {
          $title = substr($html, $titleOffset, $titleEnd-$titleOffset);
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
        $paper = "-unknown-";
      }
      else {
        $paper = $paper["name"];
      }
      
      $alt_style = "background-color: #11a;color: #fff;font-family: sans-serif;font-variant: small-caps;padding: 0px 2px 0px 2px;cursor:pointer;text-decoration: none;font-size:0.9em;font-weight:800";
      
    }
    catch (Exception $e) {
        $valid_url = false;
    }
?>
    <body>
        <div id="container">
            <?php
                if ($valid_url) {
                    echo "<p>Text:&#9;<a href='$text_url'>$text_url</a></p>";
                    echo "<p>Draft:&#9;<a href='$draft_url'>$draft_url</a></p>";
                    $s1 = "<a style='$alt_style' href='$text_url'>text</a>&nbsp;";
                    $s2 = "<a style='$alt_style' href='$draft_url'>draft</a>&nbsp;";
                    $s3 = "$paper: <a href='$url'>$title</a>";

                    echo "<div>" . 
                      "<span id='start-copy'>" . $s1 . $s2 . $s3 . "</span>" .
                      "<span id='end-copy'>&nbsp;</span>" .
                      "<button onclick='copyToClipboard()'>Copy</button>" .
                      "&nbsp;<span id='status'></span>";
                      "</div>";
                }
                else {
                    echo "<p>The input URL is invalid.</p>&nbsp;<a href='./geturl.php'>Back</a>";
                }
            ?>
        </div>
    </body>
    
<?php     
}
?>