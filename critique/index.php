<?php
  include_once "../common/version.php";
  include_once "../common/aiutility.php";
  define("PREAMBLE", "Critique this letter-to-the-editor. Do not rewrite it. Give a list of its strengths and weaknesses. Point out grammatical, syntax, and spelling errors. Format the response using HTML. Headings and Listed items must start on a new line. Here is the letter: ");
?>
<!DOCTYPE html>
<head>
  <meta name="robots" content="noindex,nofollow">
  <title>Critique My LTE</title>
  
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
  <script type="text/javascript">
    function showSpinner() {
      document.getElementById("spinner").style.display="inline";
    }
  </script>
</head>

<?php 
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<body>
  <div id="container">
    <form method="POST">
      <label for="input_url">Paste your letter here:</label><br>
      <textarea id="input_url" rows="25" cols="80" name="payload" value=""><?php echo($prompt);?></textarea>
      <br>
      <input type="submit" value="Submit" onclick="showSpinner()">
      <img id="spinner" style="display:none;padding-left:3px;height:25px;vertical-align:sub" src="loading_spinner.gif">
      <input type="hidden" id="action" name="action" value="openai">
    </form>
  </div>
</body>
<?php
  } 
  else  if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    try {
      $answer = query_ai(PREAMBLE . $_POST["payload"]);
    }
    catch (Exception $e) {
      $answer = "An exception occurred: " . $e.message();
    } 
?>
      <body>
        <div id="container">
          <div id="inner">
            <?php echo($ai_prompt);?>
            <br>
            <?php echo($answer)?>
          </div>
          <div>
            <button><a href=".">Back</a></button>
          </div>
        </div>
      </body>      
<?php }    
?>
