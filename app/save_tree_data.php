<?php
require_once('config.php');
require_once('../auth.php');
require_once('classes/SimpleLogger_Class.php');
require_once('classes/FileHandler_Class.php');

$publish_tree = $_POST['publishTree'];

$internal_tree_file = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/navigationsindex_buffer.txt';
$public_tree_file = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/navigationsindex.txt';
$dirty_indicator = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/navindex.dirty';

$jsonTreeData = $_POST['jsonTreeData'];

$jsonArray = array();
if(get_magic_quotes_gpc()) {
	$jsonArray = json_decode(stripslashes($jsonTreeData), TRUE);
} else {
	$jsonArray = json_decode($jsonTreeData, TRUE);
}

$lgr = new SimpleLogger($ne2_config_info['app_path'] . 'data/.htNavEditor.log');
$fhr = new FileHandler();

$fhr->setLogger($lgr);
$fhr->setJSONArray($jsonArray);

if($publish_tree == 'Ja') {
	$fhr->saveJSONToFile($internal_tree_file); // internal tree file always update
	$fhr->saveJSONToFile($public_tree_file, TRUE); // also make backup
	if(file_exists($dirty_indicator)) {
		unlink($dirty_indicator);
	}
} else {
	$fhr->saveJSONToFile($internal_tree_file);
	if(!file_exists($dirty_indicator)) {
		touch($dirty_indicator);
	}
}

echo('Navigation-Datei wurde gespeichert.');
?>
