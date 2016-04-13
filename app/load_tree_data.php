<?php
require_once('config.php');
//require_once('../auth.php');
require_once('classes/FileHandler_Class.php');

$internal_tree_file = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/navigationsindex_buffer.txt';
$public_tree_file = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/navigationsindex.txt';

if(!file_exists($internal_tree_file)) {
	copy($public_tree_file, $internal_tree_file);
}

$fhr = new FileHandler();
$json_array = $fhr->loadJSONFromFile($internal_tree_file); // the internal tree file

echo(json_encode($json_array));
?>
