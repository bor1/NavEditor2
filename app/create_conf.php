<?php
require_once('config.php');
require_once('../auth.php');


$fpath = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/';
$oper = $_REQUEST['oper'];
$json_data = $_REQUEST['jsonData'];
$conf_file_name = $_REQUEST['name'];
$json_arr = json_decode($json_data, TRUE);
$new_file_content = "";
if($oper == "create_conf"){
    $fpath .= $conf_file_name;
    if(!file_exists($fpath)) {
        $fh = fopen($fpath, 'w') or die('Cannot create file!');
        if(strlen($json_data) > 2){
            foreach($json_arr as $ji) {
                if($new_file_content != ""){$new_file_content .= "\r\n";}
                $new_file_content .= $ji['opt_name'] . "\t" . $ji['opt_value'];
            }
        }
        fwrite($fh, $new_file_content);
        fclose($fh);

        echo "Neue Configurationsdatei: ". $conf_file_name ." erstellt";
    }else{
        echo "Configurationsdatei: ". $conf_file_name ." bereits existiert";
    }
}else{
    echo "wrong parameter";
}

?>