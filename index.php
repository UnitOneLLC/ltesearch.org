<?php
include "lte_db.php";
define("VERSION", "2.1.2")
?>
<!DOCTYPE html>
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="ltesearch.js?ver=<?php echo VERSION;?>"></script>
    <title>LTE Article Search</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.css"/>
    <script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.js"></script>
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
				max-width: 1100px;
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
        #fetch {
            font-size: 13px;
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
     </style>
</head>

<body>
    <div id="container">
        <div id="controls">
            <select id="region">
            <?php
				$conn = new LTE_DB();
				$regions = $conn->fetch_regions();
				foreach($regions as $region) {
					echo "<option>$region</option>";
				}
				$conn = null;
            ?>
            </select>
            <div class="spacer"></div>
            <button id="fetch">Search</button>
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

</body>