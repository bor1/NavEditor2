<?php
require_once('config.php');
require_once('../auth.php');

$fpath = $_SERVER['DOCUMENT_ROOT'] .  $ne2_config_info['footerinfo_file']; 
$oper = $_REQUEST['json_oper'];


$fcontent = file_get_contents($fpath);

if($oper == 'get_content') {
	$start_pos = strpos($fcontent, $content_marker_start);
	if($start_pos !== FALSE) {


		$fcontent = str_ireplace('<!-- /footerinfos -->', '', $fcontent);
		$fcontent = trim($fcontent);

	}
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
	echo('Bereich des Fusstextes aktualisiert.');
}
?>
 