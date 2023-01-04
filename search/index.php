<?php
$maintenance = false;

include "../common/version.php";
include "../common/lte_db.php";

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
    <script src="https://accounts.google.com/gsi/client" async defer></script>
        <!-- OLD GOOGLE AUTH script src="https://apis.google.com/js/platform.js" async defer></script-->
    <script src="ltesearch.js?ver=<?php echo VERSION;?>"></script>
<?php
    }
?>
    <title>LTE Article Search</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css"/>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script src="https://apis.google.com/js/platform.js?onload=gapi_init" async defer></script>
    
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
        #digest {
            table-layout: fixed;
        }
        #digest_length {
            display: none;  /* don't show page length control */
        }
        .row-highlight {
            background-color: #ffa !important;
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
        .circle {
            padding: 0 4px;
            font-size: 16px;
            vertical-align: top;
            cursor: pointer;
        }
        table>tbody tr.selected-row, table>tbody tr.selected-row:hover  {
            background-color: #6a7 !important;
            color: white;
        }
        
        table>tbody tr.selected-row a  {
            color: yellow !important;
        }
            
        #controls div select, #controls div button {
            vertical-align: top;
        }
        #detail-select {
            display: inline-block;
        }
        #sign-out {
            float:right;
        }
        #detail-params {
            display: inline-block;
            vertical-align: top;
        }
        #paper-lookup {
            margin-left: 100px;
            width: 120px;
        }
        #sign-out {
            vertical-align: top;
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
            
            <div id="g_id_onload"
                data-client_id="139675035932-s3i8081v6er03o89aes68ujirk1b99d6.apps.googleusercontent.com"
                data-context="signin"
                data-ux_mode="popup"
                data-callback="handleGoogleAuth"
                data-auto_prompt="false">
            </div>
            
            <div class="g_id_signin"
                data-type="standard"
                data-shape="rectangular"
                data-theme="outline"
                data-text="signin_with"
                data-size="large"
                data-logo_alignment="left">
            </div>
            
            <!-- OLD GOOGLE AUTH div id="auth-btn" class="g-signin2" data-onsuccess="onSignIn" data-onfailure="onAuthFail"></div-->
        </div>
        <div id="controls">
            <div>
                <details id="detail-params">
                    <summary id="param-summary">_selected_region_/topic</summary>
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
                    <select id="topic">
                        <?php
                            if (!$maintenance) {
                                $conn = new LTE_DB();
                                $topics = $conn->fetch_topics();
                                foreach($topics as $topic) {
                                    echo "<option>$topic</option>";
                                }
                                $conn = null;
                            }
                        ?>
                    </select>
                </details>
                <div class="spacer"></div>
                <button id="fetch">Search</button>
                <div class="spacer"></div>
                <details id="detail-select">
                    <summary id="sel-sum">Selection</summary>
                    <fieldset style="display:inline-block">
                        <div style="display:inline-block">
                            <div style="display:inline-block">
                                <form id="edit-form" action="../editor/index.php" target="_blank" method="post">
                                    <button id="table-builder">Editor</button>
                                    <input id="items_json" name="items_json" type="hidden"/>
                                </form>
                            </div>
                            <button id="copy-selected">Copy selected</button><br>
                            <input id="incl-text-only" type="checkbox" name="incl-text-only" style="margin-left:20px">
                            <label for="incl-text-only" style="font-size:0.9em">include text-only links</label><br>
                            <input id="incl-create-draft" type="checkbox" name="incl-create-draft" style="margin-left:20px">
                            <label for="incl-create-draft" style="font-size:0.9em">include create draft links</label>
                        </div>
                        <div style="float:right">
                            <button id="clear-selection">Clear selection</button>
                        </div>
                        <div style="margin-top:3px; font-size:smaller; font-family: arial;">
                            Display:&nbsp
                            <input type="radio" id="radio_all" name="disp_sel" value="all">
                            <label for="all">all</label>
                            <input type="radio" id="radio_sel" name="disp_sel" value="selected">
                            <label for="css">selected only</label><br>
                        </div>
                    </fieldset>
                </details>
                
                <span id="copy-feedback"></span>

                <select id="paper-lookup" name="paper-lookup" style="display:none">
                <option value=",0">&lt;filter on paper&gt;</option>
                </select>
                <button id="sign-out">Sign out</button>
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
                    <tr><th></th><th>Date</th><th>Source</th><th>Item</th><th>Summary</th></tr>
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