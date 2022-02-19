<?php
include "../common/version.php";
include "../common/urlcode.php";

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
        $html = read_html_from_url($url);
        $titleOffset = strpos($html, "<title>");
        if ($titleOffset !== false) {
            $titleOffset += strlen("<title>");
            $titleEnd = strpos($html, "</title>");
            if ($titleEnd !== false) {
                $title = substr($html, $titleOffset, $titleEnd-$titleOffset);
            }
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
                    echo "<p>Link:&#9;<a href='$url'>$title</a>&nbsp;
                          <a style='$alt_style' href='$text_url'>text</a>&nbsp;
                          <a style='$alt_style' href='$draft_url'>draft</a></p>
                        </p>";
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