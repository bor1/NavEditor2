<?php
require_once('app/config.php');
require_once('app/classes/UserMgmt_Class.php');
require_once('app/log_funcs.php');

ini_set("session.use_only_cookies", "on");
session_start();

$cur_user = '';
if(isset($_SESSION['ne2_username'])) {
	$cur_user = $_SESSION['ne2_username'];
} else {
	$cur_user = $_COOKIE['ne2_username'];
}

function removeLockFiles($dir) {
	global $cur_user;
	if(is_dir($dir)) {
		if($dh = opendir($dir)) {
			while(FALSE !== ($file = readdir($dh))) {
				// escaped dirs
				if($file != '.' && $file != '..' && $file != 'css' && $file != 'grafiken' && $file != 'img' && $file != 'Smarty' && $file != 'ssi' && $file != 'univis' && $file != 'vkapp' && $file != 'vkdaten' && $file != 'xampp') {
					if(is_dir($dir . '/' . $file)) {
						// recursively
						removeLockFiles($dir . '/' . $file);
					} else {
						$lf = $dir . '/' . $file . '.lock';
						if(file_exists($lf)) {
							$lock_user = file_get_contents($lf);
							if(strcmp($cur_user, $lock_user) == 0) {
								@unlink($lf);
							}
						}
					}
				}
			}
			closedir($dh);
		}
	}
}

// remoive self's .lock files
$root_path = $_SERVER['DOCUMENT_ROOT'];

removeLockFiles($root_path);

// clean cookies
setcookie('ne2_username', '', time() - 7200);
setcookie('ne2_password', '', time() - 7200);
setcookie('keep_session_counter', "", time() - 7200 );		
		
unset($_SESSION['ne2_username']);
unset($_SESSION['ne2_password']);

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ausloggen - <?php echo($ne2_config_info['app_titleplain']); ?></title>
<link rel="stylesheet" type="text/css" href="css/styles.css?<?php echo date('Ymdis'); ?>" />
</head>

<body>
<div id="wrapper">
	<h1 id="header">Sie haben sich abgemeldet!</h1>
	
	<div id="contentPanel1">
		<p>Klicken Sie bitte auf <a href="login.php">diesen Link</a> um sich wieder anzumelden.</p>
	</div>
</div>
</body>

</html>