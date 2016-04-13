<?php
require_once('config.php');
require_once('../auth.php');

$root = $ne2_config_info['upload_dir'];
$dir = urldecode($_REQUEST['dir']); //TODO sicherer daten bekommen? input::?
$path = NavTools::root_filter($root . $dir);

//TODO fehlerbehandlung?
if(strcmp($path, "")==0){return;}


if (file_exists($path)) {
    if(!is_dir($path)){return;}

    $files = scandir($path);

    if (count($files) > 2) {
        natcasesort($files);
        $returnLinksArray = array();

        foreach ($files as $file) {
            if (file_exists($path . $file) && $file != '.' && $file != '..' && !is_dir($path . $file)) {
                $ext = preg_replace('/^.*\./', '', $file);
                //TODO pics ext. arrary in config ?
                if (in_array($ext, Array("jpg", "gif", "png", "jpeg"))) {
                    array_push($returnLinksArray, $dir . $file);
                }
            }
        }
    }

    echo json_encode($returnLinksArray);
}

?>
