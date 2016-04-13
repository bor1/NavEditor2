<?php
require_once('config.php');
require_once('../auth.php');

$fpath = $_SERVER['DOCUMENT_ROOT'] .  $ne2_config_info['zielgruppenmenu_file']; 
$oper = $_REQUEST['json_oper'];

$content_marker_start = '<h2 class="skip"><a name="hauptmenumarke" id="hauptmenumarke">Zielgruppennavigation</a></h2>';

$fcontent = file_get_contents($fpath);

if($oper == 'get_content') {
	$fcontent = str_replace(array('<!--#', '<!--', '-->'), array('<comment_ssi>', '<comment>', '</comment>'), $fcontent);
	echo($fcontent);
} elseif($oper == "update_content") {
	if(get_magic_quotes_gpc()) {
		$data = stripslashes($_REQUEST['content_html']);
	} else {
		$data = $_REQUEST['content_html'];
	}
	$data = str_replace(array('<comment_ssi>', '<comment>', '</comment>'), array('<!-' . '-#', '<!--', '-->'), $data);
	// recover markers
	
	file_put_contents($fpath, $data);
	echo('Bereich des Zielgruppenmenus aktualisiert!');
}
?>
