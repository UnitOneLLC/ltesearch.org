<?php
include_once "../common/version.php";
include_once "../common/urlcode.php";
include_once "../common/aiutility.php"
?>
<!DOCTYPE html>
<head>
    <meta name="robots" content="noindex,nofollow">
    <title>LTE Testing Page</title>

    <style type="text/css">
        body {
            background-color: rgba(30,30,120,1);
            color:white;
        }
        #container {
            margin: auto;
            background-color: #e8e8f0;
            color: #111;
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
  
  
      if ($_GET["action"] == "form") {
?>
        <body>
            <div id="container">
                <form method="POST">
                    <label for="input_url">Enter Prompt:</label><br>
                    <textarea id="input_url" rows="6" cols="80" name="payload" value=""></textarea>
                    <br>
                    <input type="submit" value="Submit">
                </form>
            </div>
        </body>
<?php
  } 
  else {
?>
Access denied.
<?php
  }
?>
<?php } else if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $valid_url = true;
    try {    
      $ai_prompt = $_POST["payload"];
  
      $postData = array(
        "model" => "text-davinci-003",
        "prompt" => $ai_prompt,
        "max_tokens" => 512,
        "temperature" => 1.0
      );

      $encoded_postData = json_encode($postData);
      $ai_returned_string = fetch_from_openai($encoded_postData);
      
      $decoded = json_decode($ai_returned_string);
      if ($decoded && is_array($decoded->choices) && $decoded->choices[0]->text) {
        $answer = $decoded->choices[0]->text;
      }
      else $answer = "Nothing available. " . $ai_returned_string;
    }
    catch (Exception $e) {
      $answer = "An exception occurred: " . $e.message();
    }
?>
    <body>
        <div id="container">
          <div id="inner">
            Prompt: <br>
            <?php echo($ai_prompt);?>
            <br><br>
            <?php echo($answer)?>
          </div>
        </div>
    </body>
    
<?php     
}
?>