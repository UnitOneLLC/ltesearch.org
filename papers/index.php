<?php
include_once("../common/lte_db.php");
include_once("../common/version.php");
?>
<!DOCTYPE html>
<head>
    <meta name="robots" content="noindex,nofollow">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="papers.js?ver=<?php echo VERSION;?>"></script>

    <title>LTE Newspaper Database</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.5/css/dataTables.dataTables.css" />
    <script src="https://cdn.datatables.net/2.0.5/js/dataTables.js"></script> 
    <script type="text/javascript">
        var papers_json = <?php
            $conn = new LTE_DB();
            echo json_encode($conn->fetch_papers());
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
        .url {
            width: 150px;
        }

        @media  (min-width: 600px) {
        	#container {
				max-width: 900px;
				width: 100%;
        	}
        }
        @media  (min-width: 1000px) {
        	#container {
				max-width: 1100px;
				width: 100;
        	}
        }

        .spacer {
            display: inline-block;
            margin: 0 15px 0 0;
        }
        
        #digest {
            table-layout: fixed;
        }
        #digest_length {
            display: none;  /* don't show page length control */
        }
        
        td, th {
            overflow: hidden; /* optional: hides content that exceeds cell height */
            white-space: normal; /* allows content to wrap */
        }
        

     </style>
</head>

<body>
    <div id="container">
         <div id="table-parent" style="width:100%">
			<table id="digest" class="hover stripe" style="width:100%">
				<thead>
					<tr><th>Name</th><th>Domain</th><th>LTE Address</th><th>Max words</th></tr>
				</thead>
				<tbody>

				</tbody>
			</table>
		</div>
    </div>
</body>