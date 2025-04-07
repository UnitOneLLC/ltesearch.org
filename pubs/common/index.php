<?php
	include_once("../../common/version.php");
	include_once("../../common/lte_db.php");
	include_once("../../common/urlcode.php");

  if (!defined("TEAM")) {
    define("LOGOCLASS", "logo-dual");
    define("IMGPATH", ".");
    define("LOGONAME", "dual-team.png");
    define("WGNAME", "");
    define("TEAM", "");
  }

define("MAX_PUBS", 100);
/*

    "350MA" : {
      "LOGOCLASS" : "logo-350",
      "IMGPATH" : "350ma",
      "LOGONAME" : "350-logo.png"
      "WGNAME" : "",
      "GROUPEMAIL" : "350ma-cambridge-media-team@googlegroups.com",
      "GROUPHOMEPAGE" : "https://groups.google.com/g/350ma-cambridge-media-team",
      "EMAILSUBJLINE" : "350MA LTE Team"
      "TEAM" : "350MA"

    },
    "THIRDACT" : {
      "#LOGOCLASS#" : "tam-logo",
      "#IMGPATH#" : "thirdact",
      "#LOGONAME#" : "tam-log.svg"
      "#WGNAME#" : "Massachusetts Working Group",
      "#GROUPEMAIL#" : "thirdactmalte@googlegroups.com",
      "#GROUPHOMEPAGE#" : "https://groups.google.com/g/thirdactmalte"
      "#EMAILSUBJLINE#" : "Third Act MA LTE"  
      "#TEAM#" : "TAMASS"    
    }
  }
*/  
?>
<!DOCTYPE html>
<html>
  <head>
    <meta name="robots" content="noindex, nofollow">
    <title>LTE Team Published Letters</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/lte.css">
  </head>
  <body>
    
    <!-- Navbar (sit on top) -->
    <div class="w3-top tam-static-head">
      <div class="w3-bar w3-white w3-padding w3-card">
        <img class="<?=LOGOCLASS?>" src="../image/<?=IMGPATH?>/<?=LOGONAME?>" alt="logo">
        <a href="#home" class="w3-bar-item w3-button"><b><?=WGNAME?></b>     Letters-to-the-Editor Team</a>
      </div>
    </div>
    
    <!-- Page content -->
    <div class="w3-content w3-padding" style="max-width:1564px">
      
      <!-- About Section -->
      <div class="w3-container tam-padding-header-top" id="about">
  <?php

try {
  $conn = new LTE_DB(false, "../../../etc/ltesearch.org/");
  $pubs = $conn->fetch_pubs(MAX_PUBS, TEAM);
  $conn = null;
}
catch (PDOException $e) {
  $conn = null;
  ?>
  <div class="w3-container w3-red">
    <h2>Error</h2>
    <p>There was an error connecting to the database: <?= htmlspecialchars($e->getMessage()) ?></p>
  </div>
  <?php
  exit();
}


if (count($pubs) > 0) {
  foreach ($pubs as $pub) {
    $url = parse_url($pub['url'], PHP_URL_HOST);
    $domain = preg_replace('/^www\./', '', $url);
    $logoFile = "../image/paperlogos/" . $domain . ".png";
    $formattedDate = date("m/d/Y", strtotime($pub['date']));
    ?>
    <img class="letter-image" src="<?= htmlspecialchars($logoFile) ?>" alt="Paper Logo">
    <div class="letter-meta">
      <span class="letter-author"><?= htmlspecialchars($pub['author']) ?></span>
      <span class="letter-date"><?= htmlspecialchars($formattedDate) ?></span>
    </div>
    <div class="letter-content">
      <?= nl2br(htmlspecialchars($pub['letter'])) ?>
    </div>
    <?php
  }
}
  ?>
      </div>
      
      <!-- End page content -->
    </div>
    
    
    <!-- Footer -->
    <footer class="w3-center w3-black w3-padding-16">
      <p>Powered by <a href="https://www.w3schools.com/w3css/default.asp" title="W3.CSS" target="_blank" class="w3-hover-text-green">w3.css</a></p>
    </footer>
    
  </body>
</html>

