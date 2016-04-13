<?php
require_once('../auth.php');

$conf_path = $_SERVER['DOCUMENT_ROOT'] . "/vkdaten/";

$endungen = array("",".bk",".bak");
$found = false;
$conf_file = "";
foreach($endungen as $endung){
	if(file_exists("../../../contactdata.conf".$endung)){
		$conf_file = "contactdata.conf".$endung;
		$found = true;
		break;
	}
}
//falls keine contactdata.conf gefunden wurde -> exit, return nichts.
If($found == false){
	exit();
}

$dateiname = $conf_path . $conf_file;
$datei = fopen($dateiname, "r");
$inhalt = fread($datei , filesize($dateiname));
fclose($datei);

$daten = @parseandpaste($inhalt);
print $daten;
return $daten;

function parseandpaste($inhalt) {
	if(preg_match('^name\t[\S ]*^', $inhalt, $matches)) {
		$cinst = substr($matches[0], 5);
	}
	if(preg_match('^strasse\t[\S ]*^', $inhalt, $matches)) {
		$cstreet = substr($matches[0], 8);
	}
	if(preg_match('^ort\t[\S ]*^', $inhalt, $matches)) {
		$ccity = substr($matches[0], 4);
	}
	if(preg_match('^plz\t[\S ]*^', $inhalt, $matches)) {
		$cplz = substr($matches[0], 4);
	}
	if(preg_match('^email\t[\S ]*^', $inhalt, $matches)) {
		$cemail = substr($matches[0], 6);
	}
	if(preg_match('^telefon\t[\S ]*^', $inhalt, $matches)) {
		$ctelefon = substr($matches[0], 8);
	}
	if(preg_match('^fax\t[\S ]*^', $inhalt, $matches)) {
		$cfax = substr($matches[0], 4);
	}
	if(preg_match('^kontakt1-name\t[\S ]*^', $inhalt, $matches)) {
		$cperson = substr($matches[0], 14);
	}
	if(preg_match('^kontakt1-vorname\t[\S ]*^', $inhalt, $matches)) {
		$cpersonvorname = substr($matches[0], 17);
	}

	$daten = $cinst."\\:\\".$cstreet."\\:\\".$cplz."\\:\\".$ccity."\\:\\".$cperson."\\:\\".$cpersonvorname."\\:\\".$ctelefon."\\:\\".$cfax."\\:\\".$cemail;
	return $daten;
}
?>