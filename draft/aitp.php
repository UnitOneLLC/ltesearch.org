<?php
	include_once "../common/version.php";
	include_once "../common/urlcode.php";
	include_once "../common/lte_db.php";
	include_once "../common/aiutility.php";
	
	
	
	$u = $_GET['u'];
	
	if ($u == null) {
		
		$z = $_GET['z'];
		$u = decode_url($z);
		
		if ($u == null) {
			echo "Missing URL. Nothing to do.\n";
			exit(0);
		}
	}
	
	$pro = $_GET['pro'];
	if ($pro == '0')
		$pro = false;
	else 
		$pro = true;


	$req = $_GET['req'];
	if ($req == 'tp') {
		echo get_talking_points($u, $pro);
	}
	else if ($req = 'angles') {
		echo suggest_angles($u, $pro);
	}
	else {
		echo ("Bad request. No request type was given.");
	}
?>