<?php
define("VERSION", "2.2.1/20200909");
include "lte_db.php";
?>
<!DOCTYPE html>
<head>
    <meta name="robots" content="noindex,nofollow">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="activity.js?ver=<?php echo VERSION;?>"></script>

    <title>LTE Search Activity</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css"/>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <!--meta content="width=device-width, initial-scale=1.0, user-scalable=yes" name="viewport"/-->

    <script type="text/javascript">
        var activity_json = <?php
            $conn = new LTE_DB();
            echo json_encode($conn->fetch_activity(100));
            $conn = null;
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
        #digest_length {
            display: none;  /* don't show page length control */
        }
     </style>
</head>

<body>
    <div id="container">
         <div id="table-parent">
			<table id="digest" class="hover stripe">
				<thead>
					<tr><th>Date</th><th>Region</th><th>IP Address</th><th>Topic</th><th>Results</th><th>User</th></tr>
				</thead>
				<tbody>

				</tbody>
			</table>
		</div>
    </div>
</body>