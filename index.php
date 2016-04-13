<?php
require_once ('app/config.php');

// check if first run
$fpath =  $ne2_config_info['app_path'] . 'data/' . $ne2_config_info['user_data_file_name'];
if(! file_exists($fpath)) {
        header('Location: aktivierung.php');
} else {
	header('Location: dashboard.php');
}
?>
