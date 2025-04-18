<?php
  include_once "../common/version.php";
  include_once "../common/urlcode.php";
  include_once "../common/aiutility.php";
  include_once "../common/lte_db.php";
  
  function query_ai_test($query) {
    $ch = curl_init();
    
    $url = OPEN_AI_CHAT_COMPLETION;
    $api_key = get_openai_api_key();
    $post_fields = array(
      "model" => OPENAI_MODEL,
      "messages" => array(
        array(
          "role" => "user",
          "content" => $query
        )
      ),
      "max_tokens" => 12,
      "temperature" => 0
    );
    
    $header  = [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $api_key
    ];
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
      echo 'Error: ' . curl_error($ch);
    }
    curl_close($ch);
    
    $response = json_decode($result);
    return $response->choices[0]->message->content;  
  }
  
  
  
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
  $prompt = "";
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($_GET["action"] == "openai" || $_GET['action'] == "claude") {
      $prompt = @$_GET["prompt"];
?>
<body>
  <div id="container">
    <form method="POST">
      <label for="input_url">Enter Prompt:</label><br>
      <textarea id="input_url" rows="6" cols="80" name="payload" value=""><?php echo($prompt);?></textarea>
      <br>
      <input type="submit" value="Submit">
      <input type="hidden" id="action" name="action" value="<?php echo $_GET['action'] ?>">
    </form>
  </div>
</body>
<?php
  } 
  else if ($_GET["action"] == "wine") {
?>
<body>
  <div id="container">
    <form method="POST">
      
      <p>
        Please recommend a 
        <select name="color" id="color">
          <option>Red</option>
          <option>White</option>
          <option>Rosé</option>
        </select>
        wine from 
        <select name="region" id="region">
          <option>United States</option>
          <option>France</option>
          <option>Italy</option>
          <option>China</option>
        </select>
        under 
        <select name="price" id="price">
          <option>$10</option>
          <option>$20</option>
          <option>$50</option>
          <option>$100</option>
        </select>
        that pairs well with 
        <select name="pairs" id="pairs">
          <option>red meat</option>
          <option>poultry</option>
          <option>fish</option>
          <option>Asian food</option>
          <option>cheese</option>
        </select>
        
      </p>
      <br>
      <input type="hidden" name="action" value="wine">
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
  }
  else  if ($_SERVER['REQUEST_METHOD'] === 'POST') { 

    if ($_POST["action"]=="claude") {
      $valid_url = true;
      try { 
        try {
          $conn = new LTE_DB();
          $apiKey = $conn->get_parameter("claude-api-key");
          error_log ("the api key is $apiKey");
        }
        catch (PDOException $e) {
          $conn = null;
          echo("exception "); var_dump($e);
          return;
        }
        
        // API endpoint URL
        $url = 'https://api.anthropic.com/v1/messages';

        // Prompt for the conversation
        $messages = [
          [
            'role' => 'user',
            'content' => $_POST['payload']
          ]
        ];
        
        // Convert messages to JSON
        $data = [
          'model' => 'claude-3-5-sonnet-20240620',
          'max_tokens' => 2048,
          'messages' => $messages
        ];
        
        
        $jsonData = json_encode($data);        
        error_log($jsonData);
        
        // Setup the curl data
        $curl = curl_init();
        
        curl_setopt_array($curl, [
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => $jsonData,
          CURLOPT_HTTPHEADER => [
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
            'content-type: application/json'
          ],
        ]);      
        // Execute curl
        $response = curl_exec($curl);
error_log("response from claude: $response");
        curl_close($curl);
        
        // Decode the API response
        $responseData = json_decode($response, true);        
        // Extract the assistant's reply
        $content = $responseData['content'][0]['text'];
        
        // Output the reply
        // Assuming $reply contains the response from the API
        $lines = explode("\n", $content);        
        
        // Loop through the lines
        foreach ($lines as $line) {
          // Process each line as needed
          if (preg_match('/^\*\*Day \d+:\*\*$/', $line)) {
            echo '<strong>' . $line . "</strong><br>";
          } else {
            echo $line . '<br>';
          }
        }        
        
      }
      catch (Exception $e) {
        $answer = "An exception occurred: " . $e.message();
      } ?>
<body>
  <div id="container">
    <div id="inner">
      Prompt: <br>
      <?php echo($ai_prompt);?>
      <br><br>
      <?php echo($answer)?>
    </div>
    <div>
      <br>
      <button><a href="./?action=openai&prompt=<?php echo(urlencode($ai_prompt)); ?>">New query</a></button>
    </div>
  </div>
</body>      
<?php }    
  
  
  
  
  
  
    if ($_POST["action"]=="openai") {
      $valid_url = true;
      try { 
/*        $ai_prompt = $_POST["payload"];
        
        $postData = array(
          "model" => "gpt-3.5-turbo",
          "messages" => array(
            array(
              "role" => "user",
              "content" => $ai_prompt
            )
          ),
          "max_tokens" => 127,
          "temperature" => 1.0
        );
        
        $encoded_postData = json_encode($postData);
        $ai_returned_string = fetch_from_openai($encoded_postData);
        
        $decoded = json_decode($ai_returned_string);
        if ($decoded && is_array($decoded->choices) && $decoded->choices[0]->text) {
          $answer = $decoded->choices[0]->text;
        }
        else $answer = "|$encoded_postData| "."Nothing available. " . $ai_returned_string;
*/
        $ch = curl_init();
        
        $url = 'https://api.openai.com/v1/chat/completions';
        

        
        $query = 'What is the capital city of England?';
        
        $post_fields = array(
          "model" => "gpt-3.5-turbo",
          "messages" => array(
            array(
              "role" => "user",
              "content" => $query
            )
          ),
          "max_tokens" => 12,
          "temperature" => 0
        );
        
        $header  = [
          'Content-Type: application/json',
          'Authorization: Bearer ' . $api_key
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
          echo 'Error: ' . curl_error($ch);
        }
        curl_close($ch);
        
        $response = json_decode($result);
        var_dump($response->choices[0]->message->content);  
        
        
        
        
        
        
      }
      catch (Exception $e) {
        $answer = "An exception occurred: " . $e.message();
      } ?>
      <body>
        <div id="container">
          <div id="inner">
            Prompt: <br>
            <?php echo($ai_prompt);?>
            <br><br>
            <?php echo($answer)?>
          </div>
          <div>
            <br>
            <button><a href="./?action=openai&prompt=<?php echo(urlencode($ai_prompt)); ?>">New query</a></button>
          </div>
        </div>
      </body>      
    <?php }    
    if ($_POST["action"]=="wine") {
      $valid_url = true;
      try {    
        $ai_prompt = $_POST["payload"];
        
        $postData = array(
          "model" => "text-davinci-003",
          "prompt" => $ai_prompt,
          "max_tokens" => 1024,
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
      <?php 
        try {                    
          $color = $_POST["color"];
          $price = $_POST["price"];
          $region = $_POST["region"];
          $pairs = $_POST["region"];
          
          $ai_prompt = "Please recommend a $color wine from $region priced below $price that pairs well with $pairs";
          
          $postData = array(
            "model" => "text-davinci-003",
            "prompt" => $ai_prompt,
            "max_tokens" => 1024,
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
        
        echo($answer)
      ?>
      <br>
      <button><a href="./?action=wine">New query</a></button>
    </div>
  </div>
</body>
<?php
}
}
?>
