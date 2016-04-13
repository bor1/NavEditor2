<?php
require_once('config.php');
require_once('../auth.php');

$fpath = $ne2_config_info['upload_dir'];
$oper = $_REQUEST['oper'];
$fname = $_REQUEST['file_name'];

if($oper == 'delete') {
	if(file_exists($fpath . $fname)) {
		@unlink($fpath . $fname);
		@unlink($fpath . 'thumb_' . $fname);
	}
	echo('[OK]');
}
?>
