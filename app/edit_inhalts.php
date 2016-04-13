<?php
require_once('config.php');
require_once('../auth.php');



$fpath = $_SERVER['DOCUMENT_ROOT'] . $ne2_config_info['inhaltsinfo_file'];
$oper = $_REQUEST['json_oper'];

$content_marker_startdivold   = '<div id="kurzinfo">';
$content_marker_start =  $ne2_config_info['inhaltsinfo_content_marker_start'];
$content_marker_end =  $ne2_config_info['inhaltsinfo_content_marker_end'] ;



$fcontent = file_get_contents($fpath);



if($oper == 'get_content') {

	if ( strpos($fcontent, $content_marker_end) > 1) {
	      $fcontent = str_replace($content_marker_end,'', $fcontent);
	 } else {
	      // fallback mit RegExp:
		$fcontent = preg_replace('/<\/div>\s*(<!-- end: inhaltsinfo -->|)[\s\n]*$/','', $fcontent);
	}

	$start_pos = strpos($fcontent, $content_marker_start);
	if($start_pos !== FALSE) {
		$start_pos += strlen($content_marker_start);
		$fcontent = substr($fcontent, $start_pos);
		// omit start marker	default
	} else {

			$start_pos = strpos($fcontent, $content_marker_startdivold );
			if($start_pos !== FALSE) {
				$start_pos += strlen($content_marker_startdivold );
				$fcontent = substr($fcontent, $start_pos);
				 // omit start marker	 old
			} else {
				$fcontent = preg_replace('/^\s*<div id="inhaltsinfo"\s*>\s*/','', $fcontent);
			}

	}
	$fcontent = str_replace(array('<!--#', '<!--', '-->'), array('<comment_ssi>', '<comment>', '</comment>'), $fcontent);
	echo($fcontent);


} elseif($oper == "update_content") {

	if(get_magic_quotes_gpc()) {

		$data = stripslashes($_POST['content_html']);

	} else {

		$data = $_POST['content_html'];

	}

	$data = str_replace(array('<comment_ssi>', '<comment>', '</comment>'), array('<!-' . '-#', '<!--', '-->'), $data);

	// recover markers


	$data = $content_marker_start."\n\n".$data."\n\n".$content_marker_end;


	file_put_contents($fpath, $data);

	echo('Inhaltsinfo aktualisiert.');

}


?>