<?php
include "../common/version.php";
?>
<!DOCTYPE html>
<head>
    <meta name="robots" content="noindex,nofollow">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="editor.js?ver=<?php echo VERSION;?>"></script>

    <title>LTE Editor</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css"/>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/rowreorder/1.2.8/js/dataTables.rowReorder.min.js"></script>
    <script type="text/javascript">
        var items = <?php
            echo $_POST["items_json"];
        ?>;
        var bEnableSocial = <?php
            echo $_POST["enable_social"];
        ?>
    </script>

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
				max-width: 1100px;
				width: 75%;
        	}
        }
    
        .spacer {
            display: inline-block;
            margin: 0 15px 0 0;
        }
        #result_table_length, #result_table_filter, #result_table_info, #result_table_paginate {
            display: none;  /* don't show page length and search controls */
        }
        
        td:first-child {cursor: grab}
        
        #copy-btn, #add-url-btn {
            margin:10px 0 0 0;
        }
        #url-to-add-input {
            width: 300px;
        }
        #controls {
            background-color: #777;
            color: white;
            padding: 10px;
            margin-top: 10px;
        }
        #help-pane {
            padding: 3px;
            margin: 5px 0;
            font-style: italic;
        }
        .del-btn {
            font-weight: 800;
            color: white;
            text-align: center;
            cursor:pointer;
            background-color: transparent;
            user-select: none;
        }
        tr:hover .del-btn {
            color: red;
        }
        
     </style>
</head>

<body>
    <div contenteditable id="container">
        <div id="head" contenteditable style="min-height:20px;">
            Good Morning,<br>
            <br>
            Here are today's links.<br>
        </div>
         <div id="table-parent">
			<table id="result_table" class="hover stripe">
				<tbody>
				</tbody>
			</table>
            <div id="after-table">&nbsp;</div>
		</div>
        <div id="foot" contenteditable style="min-height:20px;">
            Thanks for writing.<br>
            <br>
            &lt;signature&gt;
            <br>
        </div>
        <div id="_end_"></div>
        <div id="buffer-for-chrome">&nbsp</div>
        <div id="controls">
            <div>
                <span>Add another link:</span>&nbsp;<input id="url-to-add-input" type="text"><button id="add-url-btn">Add</button>
                <button id="toggle-help" style="display:inline-div;float:right">?</button>
            </div>
            <div>
                <button id="copy-btn" title="Copy the letter to the clipboard">Copy email to clipboard</button>&nbsp;<span id="copy-feedback"></span>
            </div>
            <div id="help-pane">
            </div>
        </div>
    </div>
</body>
