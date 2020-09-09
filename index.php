<?php
$maintenance = false;

include "lte_db.php";
define("VERSION", "2.2.5/20200724");
define("BOOKMARK_URL", "https://groups.google.com/forum/#!forum/350ma-cambridge-media-team");
define("BOOKMARK_TEXT", "350 MA Cambridge Media Team");

function _bot_detected() {
//https://stackoverflow.com/questions/677419/how-to-detect-search-engine-bots-with-php
    return (
        isset($_SERVER['HTTP_USER_AGENT'])
        && preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])
    );
}

?>
<!DOCTYPE html>
<head>
    <meta name="robots" content="noindex,nofollow">
    <meta name="google-signin-client_id" content="139675035932-s3i8081v6er03o89aes68ujirk1b99d6.apps.googleusercontent.com">
<?php
    if (!$maintenance) {
?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <script src="ltesearch.js?ver=<?php echo VERSION;?>"></script>
<?php
    }
?>
    <title>LTE Article Search</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css"/>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <!--meta content="width=device-width, initial-scale=1.0, user-scalable=yes" name="viewport"/-->

    <style type="text/css">
        body {
           background-color: rgba(30,30,120,1);
        }
        #container {
            margin: auto;
            background-color: #e8e8f0;
            padding: 10px;
        }
        
        @media  (min-width: 600px) {
            #container {
                max-width: 900px;
                width: 66%;
            }
        }
        @media  (min-width: 1000px) {
            #container {
                max-width: 1000px;
                width: 75%;
            }
        }
        
        #controls {
            background-color: #e8e8e8;
            padding: 0 0 5px;
            border-bottom: solid 1px #aaa;
            margin-bottom: 20px;
        }
        .spacer {
            display: inline-block;
            margin: 0 15px 0 0;
        }
        #fetch, #sign-out {
            font-size: 13px;
            margin: 0 20px;
        }

        #loading {
            background-color: white;
            height: 35px;
            margin-top: 10px;
            padding: 0 0 10px 10px;
        }
        #loading img {
            vertical-align: middle;
        }
        #digest_length {
            display: none;  /* don't show page length control */
        }
        .row-highlight {
            background-color: #ffa !important;
        }
        #bookmark {
            float: right;
        }
        #ver {
            position: absolute;
            bottom: 10px;
            color: #88A;
            font-size: 12px;
            padding-right: 6px;
            font-family: arial;
            width: 100%;
        }
        #auth-prompt {
            vertical-align: top;
        }
        #auth-btn {
            display: inline-block;
            margin: 0 10px;
        }
     </style>
</head>

<body>
<?php
    if ($maintenance or _bot_detected()) {
?>
    <DIV style="text-align:center; padding:20px;color:white;font-weight:bold;font-size: 30px">**** ltesearch.org is undergoing maintenance right now -- try again later ****</DIV>
<?php
    }
    else {    
?>    
    <div id="container">
        <div id="auth">
            <span id="auth-prompt">You must sign in. </span>
            <div id="auth-btn" class="g-signin2" data-onsuccess="onSignIn"></div>
        </div>
        <div id="controls">
            <div>
                <select id="region">
                <?php
                    if (!$maintenance) {
                        $conn = new LTE_DB();
                        $regions = $conn->fetch_regions();
                        foreach($regions as $region) {
                            echo "<option>$region</option>";
                        }
                        $conn = null;
                    }
                ?>
                </select>
                <div class="spacer"></div>
                <button id="fetch">Search</button>
                <button id="sign-out">Sign out</button>
                <a id="bookmark" href="<?php echo BOOKMARK_URL; ?>" target="_other"><?php echo BOOKMARK_TEXT; ?></a>
            </div>
            <div class="spacer100">     </div>
            <div id = "loading">
                Searching . . .
                <img height="45px" src="loading_spinner.gif">
            </div>
         </div>
         <div id="table-parent">
            <table id="digest" class="hover stripe">
                <thead>
                    <tr><th>Date</th><th>Source</th><th>Item</th><th>Summary</th></tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
    <div id='ver'>Version <?php echo VERSION;?></div>
<?php }
?>
</body>