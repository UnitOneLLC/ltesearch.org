<?php
  include_once "../common/version.php";
  include_once "../common/aiutility.php";
  define("PREAMBLE", "Critique this letter-to-the-editor. Do not rewrite it. First, report the count of words in the letter. If the word count is more than 200, include a warning that the guidelines usually suggest a maximum of 200 words. Then, give a list of its strengths and weaknesses. Also, very important, point out grammatical, syntax, and spelling errors. You must also note whether or not the letter is coherent and expresses a clear viewpoint. Point out any characteristics of the letter that would make it unlikely to be published. You may also suggest minor wording changes if they would improve clarity. Format the response using HTML. Headings and Listed items must start on a new line. Here is the letter: ");
?>
<!DOCTYPE html>
<head>
  <meta name="robots" content="noindex,nofollow">
  <title>Critique My LTE</title>
  
  <style type="text/css">
    body, #top-box {
      background-color: rgba(30,30,120,1);
      color:white;
      font-family: sans-serif;
    }
    #container, #top-box {
      margin: auto;
      background-color: #e8e8f0;
      color: #111;
      padding: 10px;
      overflow-wrap: break-word;
    }
    #top-box {
      color: black;
    }
    header {
      font-size: 24px;
    }
    #instructions {
      padding: 10px 0;
    }
    
    @media  (min-width: 600px) {
      #container, #top-box {
        max-width: 500px;
        width: 66%;
      }
    }
    @media  (min-width: 1000px) {
      #container, #top-box {
        max-width: 800px;
        width: 75%;
      }
    }
    textarea {
        width: 90%;
    } 
  </style>
  <script type="text/javascript">
    function showSpinner() {
      document.getElementById("spinner").style.display="inline";
    }
    
    function onLoad() {
      document.getElementById('pasteButton').addEventListener('click', async () => {
        try {
          // Check if the Clipboard API is available
          if (navigator.clipboard && navigator.clipboard.readText) {
            const text = await navigator.clipboard.readText(); // Get clipboard text
            document.getElementById('input_url').value = text; // Set the textarea value
          } else {
            alert('Clipboard API not available in your browser.');
          }
        } catch (error) {
          console.error('Failed to paste clipboard text:', error);
          alert('Could not paste clipboard text. Please ensure clipboard permissions are enabled.');
        }
      });
    }
    
  document.addEventListener('DOMContentLoaded', onLoad);
  
  </script>
</head>

<?php 
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<body>
  <div id="top-box">
    <header>Letter Critique</header>
    <div id="instructions">
        After you have a draft of your letter, you can copy it to the clipboard and
        paste it into the form below. Click <b>Get Critique</b>, and the AI will assess the
        strengths and weaknesses of your letter, as well as checking for grammatical
        errors.
    </div>
  </div>
  <div id="container">
    <div><button id="pasteButton">Paste</button></div>
    <form method="POST">
      <textarea id="input_url" rows="25" cols="80" name="payload" value=""></textarea>
      <br>
      <input type="submit" value="Get Critique" onclick="showSpinner()">
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
            <?php echo($answer)?>
          </div>
          <div>
            <button><a href=".">Back</a></button>
          </div>
        </div>
      </body>      
<?php }    
?>
