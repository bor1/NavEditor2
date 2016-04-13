<?php
require_once('../auth.php');

function result($inst, $street, $plz, $city, $person, $personvorname, $telefon, $fax, $email) {
	$contactdata = "name\t" . $inst . "\nstrasse\t" . $street . "\nort\t" . $city . "\nplz\t" . $plz . "\nemail\t" . $email . "\ntelefon\t" . $telefon . "\nfax\t" . $fax . "\nkontakt1-name\t" . $person . "\nkontakt1-vorname\t" . personvorname . "\n";

	$shtml = "
	<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"
		\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
	<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"de\" lang=\"de\">
		<head>
		<title>Kontakt</title>
		<!--#include virtual=\"/ssi/head.shtml\" -->

		<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />
		<meta http-equiv=\"content-script-type\" content=\"text/javascript\" />
		<meta http-equiv=\"content-style-type\" content=\"text/css\" />
		<meta http-equiv=\"content-language\" content=\"de\" />

	
	</head>
	<body>
		<div id=\"seite\">
			<a name=\"seitenmarke\" id=\"seitenmarke\"></a>
	<!-- KOPF ******************************************************************** -->
	<!-- ************************************************************************** -->
	<div id=\"kopf\">
		<div id=\"logo\"></div>
		<div id=\"titel\">
			<h1>Kontakt</h1>
		</div>
		<!--#include virtual=\"/ssi/kopf.shtml\" -->
	</div>
	<hr id=\"nachkopf\" />
	<!-- MENU ********************************************************************* -->
	<!-- ************************************************************************** -->
	<div id=\"main\">
		<div id=\"menu\">
			<div id=\"bereichsmenu\">
				<h2><a name=\"bereichsmenumarke\" id=\"bereichsmenumarke\">Bereichsmenu</a></h2>
			<!--#include virtual=\"/cgi-bin/navigation/navigation.pl\" -->
			</div>
			<!--#include virtual=\"/ssi/kurzinfo.shtml\" -->
		</div>
	<!-- CONTENT ****************************************************************** -->
	<!-- ************************************************************************** -->
		<div id=\"content\">
			<a name=\"contentmarke\" id=\"contentmarke\"></a>
			<!-- Inhaltsinfo ************************************************************** -->
			<!-- ************************************************************************** -->
			<!--#include virtual=\"/ssi/inhaltsinfo.shtml\" -->
			<!-- TEXT AB HIER -->
			<table>
			<div class=\"vcard\">
				<tr class=\"eins\">
					<th class=\"zeile\">Adresse</th>
					<td>
						<span class=\"org\">" . $inst . "</span><br />" . $personvorname . " " . $person . "<br/><span class=\"street-address\">" . $street . "</span><br /><span class=\"postal-code\">" . $plz . "</span> <span class=\"locality\">" . $city . "</span>
					</td>
				</tr>
				<tr class=\"zwei\">
					<th class=\"zeile\">Telefon</th>
					<td><span class=\"tel\">" . $telefon . "</span></td>
				</tr>";

				if(!empty($fax)) {
					$shtml = $shtml . "
					<tr class=\"eins\">
						<th class=\"zeile\">Fax</th>
						<td><span class=\"fax\">" . $fax . "</span></td>
					</tr>";
				}

				$shtml = $shtml . "
					<tr class=\"eins\">
						<th class=\"zeile\">E-Mail</th>
						<td><span class=\"email\"><a href=\"mailto:" . $email . "\">" . $email . "</a></span></td>
					</tr>
				</div>
			</table>
	<!-- AB HIER KEIN TEXT MEHR -->
		<hr id=\"vorfooter\" />
	</div><!-- /content -->
	<!-- FOOTER ******************************************************************* -->
	<!-- ************************************************************************** -->
		</div><!-- /main -->
		<div id=\"footer\">
			<h2><a name=\"footermarke\" id=\"footermarke\">Statusinformationen zur Seite</a></h2>
			<p>
			Letzte &Auml;nderung:
			<!--#config timefmt=\"%d.%m.%Y um %H:%M Uhr\"--><!--#echo var=\"LAST_MODIFIED\"-->
			</p>
			<div id=\"footerinfos\">
				<div id=\"tecmenu\">
				<h2 class=\"u\"><a name=\"hilfemarke\" id=\"hilfemarke\">Technisches Menu</a></h2>
				<!--#include virtual=\"/cgi-bin/navigation/inhalt.pl?type=sonder&noU=1&nodfn=1&nochilds=1\" -->
				</div>
				<!-- ZUSATZINFO *************************************************************** -->
				<!-- ************************************************************************** -->
				<!--#include virtual=\"/ssi/zusatzinfo.shtml\" -->
			</div><!-- /footerinfos -->
			</div>
		</div><!-- /seite -->
	</body>
	</html>";

	return $shtml;
}

$shtml = result(entities($_POST["inst"]), entities($_POST["street"]), entities($_POST["plz"]), entities($_POST["city"]), entities($_POST["personname"]), entities($_POST["personvorname"]), entities($_POST["telefon"]), entities($_POST["fax"]), entities($_POST["email"]));

$contactdata = "name\t".$_POST["inst"]."\nstrasse\t".$_POST["street"]."\nplz\t".$_POST["plz"]."\nort\t".$_POST["city"]."\nkontakt1-name\t".$_POST["personname"]."\nkontakt1-vorname\t".$_POST["personvorname"]."\ntelefon\t".$_POST["telefon"]."\nfax\t".$_POST["fax"]."\nemail\t".$_POST["email"];

$dateiname2 = $_SERVER['DOCUMENT_ROOT']."/kontakt.shtml";
$datei2 = fopen($dateiname2, "w");

fwrite($datei2, $shtml);
fclose($datei2);

//$dateiname3 = $_SERVER['DOCUMENT_ROOT']."/vkdaten/contactdata.conf";
//$datei3 = fopen($dateiname3, "w");

//fwrite($datei3, $contactdata);
//fclose($datei3);

function entities($string) {
		return htmlentities($string, ENT_COMPAT, "UTF-8", FALSE);
}
?>