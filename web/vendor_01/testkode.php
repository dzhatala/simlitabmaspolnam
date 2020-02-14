<?PHP
		$cfgProgDir = $_SERVER["DOCUMENT_ROOT"]. "/phpSecurePages/"; 
		$cfgUserdir = $_SERVER["DOCUMENT_ROOT"]. "/"; 
		include($INC_DIR. "common.php"); 
		//$minUserLevel = 1; 
		$requiredUserLevel = array(100);

        //$cfgProgDir = 'phpSecurePages/';
        include($cfgProgDir . "secure.php");
?>

<?php
	echo "<BR> <BR>  KODE RAHASIA <br><br>\n";
	echo "<BR> KODE1 : ".$_GET['login']."<br>\n";
	echo "KODE2  : ". $_GET['addr']."<br>\n";
	$src3=$_GET['login'].$_GET['addr'];
	$md51=md5($src3);
	$kode3=substr($md51,1,4);
	echo "KODE3  : ". $kode3."<br>\n";
	
	
	
?>

<a href="/logout.php"> LOG OUT </a>
