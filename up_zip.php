<?php
/* Simple script to upload a zip file to the webserver and have it unzipped
 *        Saves tons of time, think only of uploading Wordpress to the server
 *               Thanks to c.bavota (www.bavotasan.com)
 *                      I have modified the script a little to make it more convenient
 *                             Modified by: Johan van de Merwe (12.02.2013)
 */

require_once('auth.php');
require_once('app/config.php');

$custom_css_classes = '';
if($ne2_config_info['custom_content_css_classes'] != '') {
	$custom_css_classes = array();
	$arr_cls = explode('|', $ne2_config_info['custom_content_css_classes']);
	foreach($arr_cls as $ac) {
		array_push($custom_css_classes, $ac . '=' . $ac);
	}
	$custom_css_classes = implode(';', $custom_css_classes);
}

// help
function has_help_file() {
	global $ne2_config_info;
	$help_file = $ne2_config_info['help_path'] .'ma_editor'. $ne2_config_info['help_filesuffix'] ;
	return file_exists($help_file);
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Datei zu Lehrveranstaltung hinzuf&uuml;gen <?php echo($ne2_config_info['app_titleplain']); ?></title>
<link rel="stylesheet" type="text/css" href="css/styles.css?<?php echo date('Ymdis'); ?>" />

<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
function please_confirm() {
	return confirm('Sind Sie sicher das Sie die Datei hochladen wollen, falls eine Datei für die Lehrveranstaltung bereits existiert, wird diese &uuml;berschrieben?');
}
tinyMCE.init({
	mode: "textareas",
	language: "de",
	theme: "advanced",
	skin: "o2k7",
	relative_urls: false,
	convert_urls: false,
	plugins: "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

	theme_advanced_buttons1: "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,styleselect,|,bullist,numlist,outdent,indent,|,cut,copy,paste,pastetext,pasteword,|,undo,redo",
	theme_advanced_buttons2: "link,unlink,anchor,image,cleanup,|,charmap,emotions,iespell,|,ltr,rtl,|,fullscreen,help,code,|,addIB1,addIB2,addIB3",
	theme_advanced_buttons3: "",

	theme_advanced_toolbar_location: "top",
	theme_advanced_toolbar_align: "left",
	theme_advanced_statusbar_location: "bottom",
	theme_advanced_blockformats: "p,address,pre,h2,h3,h4,h5,h6,blockquote,code",
	theme_advanced_styles: "<?php echo($custom_css_classes); ?>",
	setup: function(ed) {
		// add a custom button
		ed.addButton("addIB1", {
			title: "Inhaltsblock-1 einfügen",
			image: "/vkdaten/tools/NavEditor2/css/ib1.gif",
			onclick: function() {
				ed.focus();
				var divId = "custom" + Math.random();
				divId = divId.replace(/\./, "_");
				ed.selection.setContent("<h2><a href=\"javascript:anzeigen('" + divId + "')\">Titeltext</a></h2><div id=\"" + divId + "\" style=\"display: block;\"><ul><li>List-Item-1</li><li>List-Item-2</li></ul><p class=\"noprint\"><a href=\"javascript:anzeigen('" + divId + "')\">Schlie&szlig;en</a></p></div>");
			}
		});
		// add a custom button
		ed.addButton("addIB2", {
			title: "Inhaltsblock-2 einfügen",
			image: "/vkdaten/tools/NavEditor2/css/ib2.gif",
			onclick: function() {
				ed.focus();
				var divId = "custom" + Math.random();
				divId = divId.replace(/\./, "_");
				ed.selection.setContent("<h3><a href=\"javascript:anzeigen('" + divId + "')\">Titeltext</a></h3><div id=\"" + divId + "\" style=\"display: block;\"><ul><li>List-Item-1</li><li>List-Item-2</li></ul><p class=\"noprint\"><a href=\"javascript:anzeigen('" + divId + "')\">Schlie&szlig;en</a></p></div>");
			}
		});
		// add a custom button
		ed.addButton("addIB3", {
			title: "Inhaltsblock-3 einfügen",
			image: "/vkdaten/tools/NavEditor2/css/ib3.gif",
			onclick: function() {
				ed.focus();
				var divId = "custom" + Math.random();
				divId = divId.replace(/\./, "_");
				ed.selection.setContent("<h4><a href=\"javascript:anzeigen('" + divId + "')\">Titeltext</a></h4><div id=\"" + divId + "\" style=\"display: block;\"><ul><li>List-Item-1</li><li>List-Item-2</li></ul><p class=\"noprint\"><a href=\"javascript:anzeigen('" + divId + "')\">Schlie&szlig;en</a></p></div>");
			}
		});
	}
});
</script>

<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="js/json2.js"></script>
<script type="text/javascript" src="js/ajaxfileupload.js"></script>
<?php
function get_http_response_code($theURL) {
    $headers = get_headers($theURL);
    return substr($headers[0], 9, 3);
}
if($_FILES["zip_file"]["name"]) {
	$filename = $_FILES["zip_file"]["name"];
	$source = $_FILES["zip_file"]["tmp_name"];
	$type = $_FILES["zip_file"]["type"];

	$name = explode(".", $filename);
	$accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
	foreach($accepted_types as $mime_type) {
		if($mime_type == $type) {
			$okay = true;
			break;
		}
	}

	$continue = strtolower($name[1]) == 'zip' ? true : false;
	if(!$continue) {
		$message = "Die Datei is keine .zip Datei, deswegen wurde das Hochladen abgebrochen. Bitte versuchen Sie es erneut mit einer .zip Datei.";
	} else {

		/* PHP current path */
		$path = dirname(__FILE__).'/';  // absolute path to the directory where zipper.php is in
		$lvurl = $_POST['txtLvUrl'];

		$response_code = get_http_response_code($lvurl);
				//XXX check if url['host'] isset
		$url = parse_url($lvurl);
		if($response_code=="200" && $url['host']=="univis.fau.de" && isset($url['query'])) {
			$queries = explode("&", $url['query']);
			$lvs_id = '';
			$path_to_lvs = '';
			foreach($queries as $attr) {
				$pos = strpos($attr,"lvs=");
				if( $pos !== false) {
					$lvs_id = substr($attr, strrpos($attr, '/') + 1);
					$path_to_lvs = substr($attr, strpos($attr, '=')+1);
					break;
				}
			}
			$univis_path=  $_SERVER['DOCUMENT_ROOT'] . '/univis-daten/lehrveranstaltungen-einzeln/';
			$targetzip = $univis_path . $lvs_id . ".zip"; // target zip file
			if(!is_dir($univis_path))
				mkdir($univis_path, 0777);
			//copy($source, $targetzip);
			if(move_uploaded_file($source, $targetzip)) {
				/*TODO extract the zip or not?
				$zip = new ZipArchive();
				$x = $zip->open($targetzip);  // open the zip file to extract
				if ($x === true) {
					$zip->extractTo($targetdir); // place in the directory with same name
					$zip->close();
					unlink($targetzip);
				}*/
				$message = "Ihre .zip Datei wurde erfolgreich hochgeladen und entpackt.";
			} else {
				$message = "Es gab ein Problem bei dem hochladen. Bitte versuchen Sie es erneut. Achten Sie auf korrekte URL. \
				<a href='http://univis.fau.de' target='_blank'>Hier</a> k&ouml;nnen Sie nach Ihrer veranstaltung suchen";
			}
		}	else {
			$message = "URL nicht g&uuml;ltig oder nicht auf univis.fau.de. <a href='http://univis.fau.de' target='_blank'	>Hier</a> k&ouml;nnen Sie nach Ihrer veranstaltung suchen";
		}
	}
}

?>
</head>

<body id="bd_MA">
<div id="wrapper">
	<h1 id="header"><?php echo($ne2_config_info['app_title']); ?></h1>
	<div id="navBar">
		<?php require('common_nav_menu.php'); ?>
	</div>

	<div id="confList">
		<fieldset>
			<legend>UnivIS-ID</legend>
			<input type="text" id="txtUnivISId" class="textBox" size="16" />
			<input type="button" id="btnSetUnivISId" class="button" value="setzen" />
		</fieldset>
	</div>

	<div id="contentPanel2">
	<?php
	// help
	if(has_help_file()) {
	?>
		<div id="helpCont">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</div>
		<div id="helpHand"><a href="javascript:;">Hilfe</a></div>
	<?php
	}
	?>
		<fieldset id="fld_feedimport">
				<legend>Datei zur Lehrveranstaltung hinzufügen</legend>
				<?php //if($message) echo "<p>$message</p>"; ?>
   			<br/>
				Hier können Sie eine Zip-Datei einer Lehrveranstaltung hinterlegen. <b>Sollten Sie bereits eine Zip hinterlegt haben, so wird diese &uuml;berschrieben.</b><br/>
				Um die Zip-Datei der korrekten veranstaltung zuzuordnen, geben Sie bitte die Url der Lehrverantaltung aus dem Univis ein.
        	<form enctype="multipart/form-data" method="post" action="">
				<p>
					<label for="LvId" style="text-align:right;">UNIVIS-Url:</label>
					<input type="text" id="txtLvUrl" name="txtLvUrl" size="32" class="textBox" />
				</p>
				<br />
	<?php if($message) echo "<p>$message</p>"; ?>
    <form enctype="multipart/form-data" method="post" onsubmit="return please_confirm();">
			<label>Eine Datei auswählen: <input type="file" name="zip_file" /></label>
			<input type="submit" name="submit" value="Hochladen" onsubmit="return please_confirm();"/>
		</form>
		</fieldset>
	</div>

<?php require('common_footer.php'); ?>
</div>
</body>
</html>
