<?php
include "../common/version.php";
?>
<!DOCTYPE html>
<head>
    <meta name="robots" content="noindex,nofollow">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="editor.js?ver=<?php echo VERSION;?>"></script>

    <title>LTE Editor</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css"/>

    <script type="text/javascript" src="https://cdn.datatables.net/2.0.0/js/dataTables.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/rowreorder/1.5.0/js/dataTables.rowReorder.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/rowreorder/1.5.0/js/rowReorder.dataTables.js"></script>
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
        #adder {
            margin: auto;
            padding: 10px;
        }
        
        @media  (min-width: 600px) {
        	#container, #adder {
			/*	max-width: 900px; */
				width: 66%;
        	}
        }
        @media  (min-width: 1000px) {
        	#container, #adder {
			/*	max-width: 1100px;*/
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
        #controls, #adder {
            background-color: #777;
            color: white;
            padding: 10px;
            margin-top: 10px;
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
        .dtMoveUp, .dtMoveDown {
            font-size: 1.2em;
            font-weight: 800;
            cursor: pointer;
        }
        
     </style>
</head>

<body>
    <div id="adder">
        <span>Add another link:</span>&nbsp;<input id="url-to-add-input" type="text"><button id="add-url-btn">Add</button>
    </div>

    <div contenteditable id="container">
         <div id="table-parent">
			<table id="result_table" class="hover stripe" style="width:100%">
				<tbody>
				</tbody>
			</table>
            <div id="after-table">&nbsp;</div>
		</div>
        <div id="buffer-for-chrome">&nbsp</div>
        <div id="controls">
            <div>
                <button id="copy-btn" title="Copy table to the clipboard">Copy</button>&nbsp;<span id="copy-feedback"></span>
            </div>
        </div>
    </div>
    <div id="copy_buffer" style="padding:20px">
        &nbsp;<br>
    </div>
</body>
