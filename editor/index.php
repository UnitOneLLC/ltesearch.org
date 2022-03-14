<?php
include "../common/version.php";
?>
<!DOCTYPE html>
<head>
    <meta name="robots" content="noindex,nofollow">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="editor.js?ver=<?php echo VERSION;?>"></script>

    <title>LTE Search Results Editor</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css"/>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/rowreorder/1.2.8/js/dataTables.rowReorder.min.js"></script>
    <script type="text/javascript">
        var items = <?php
            echo $_POST["items_json"];
        ?>;
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
        
        #copy-btn {
            margin-top:10px;
        }
        
     </style>
</head>

<body>
    <div id="container">
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
            <br>
            Thanks for writing.<br>
            <br>
            sig
            <br>
        </div>
        <div id="_end_"></div>
        <button id="copy-btn">Copy</button>&nbsp;<span id="copy-feedback"></span>
    </div>
</body>
