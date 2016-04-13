<?php
require_once('auth.php');
require_once('app/config.php');


$caches = Array(
    'feeds' => Array('path' => 'feeds/cache/'),
    'uniportal' => Array('path' => 'uniportal/cache/'),
    'univis' => Array('path' => 'univis/cache/')
);

//bestimmen was wir loeschen,
// falls "all" als parameter, alle caches loeschen
$toRemovePaths = Array();
foreach ($caches as $cacheName => $cacheArray) {
    if (isset($_REQUEST[$cacheName]) || isset($_REQUEST['all'])) {
        array_push($toRemovePaths, $cacheArray['path']);
    }
}


//function delTree($dir, $bClearOnly) {
//    if(!is_dir($dir)) return false;
//
//    $files = array_diff(scandir($dir), array('.', '..'));
//    foreach ($files as $file) {
//        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
//    }
//
//    if(!$bClearOnly) {rmdir($dir);}
//}

function delFilesInFolder($dir){
    if(!is_dir($dir)) return false;

    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        unlink("$dir$file");
//        echo "$dir$file";
    }
}

//falls es etwas zu loeschen gibt
if(is_array($toRemovePaths) && count($toRemovePaths) > 0){
    //loeschen
    foreach($toRemovePaths as $pathToClear){
        delFilesInFolder($ne2_config_info['cgi-bin_path'].$pathToClear);
    }
}


// help
function has_help_file() {
	global $ne2_config_info;
	$help_file = $ne2_config_info['help_path'] .'remove_caches'. $ne2_config_info['help_filesuffix'] ;
	return file_exists($help_file);
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Cache leeren - <?php echo($ne2_config_info['app_titleplain']); ?></title>

<?php
    echo NavTools::includeHtml("default",
                    "jqueryui/ne2-theme/jquery-ui-1.8.17.custom.css",
                    "jquery-ui-1.8.18.custom.min.js");
?>

<script type="text/javascript">
$(document).ready(function() {

        $("button.delete_cache_btn").button();
});
</script>
</head>

<body id="bd_removeCaches">
<div id="wrapper">
	<h1 id="header"><?php echo($ne2_config_info['app_title']); ?></h1>
	<div id="navBar">
		<?php require('common_nav_menu.php'); ?>
	</div>

    <div id="contentPanel1">
        <?php
        // help
        if (has_help_file()) {
        ?>
            <div id="helpCont"></div>
            <div id="helpHand"><a href="javascript:;">Hilfe</a></div>
        <?php
        }
        ?>


        <?php
            function createEntity($text,$param){
                $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
                $port = ($_SERVER["SERVER_PORT"] == "80") ? "":":".$_SERVER["SERVER_PORT"];
                $linkToOpen = $_SERVER["SERVER_NAME"].$port.$uri_parts[0].'?'.$param;
//                echo "<p>".$text."</p>\n";
                echo "<button type='submit' style='width: 250px;' class='delete_cache_btn' onclick ='window.location = \"http://".$linkToOpen."\"'>".$text."</button><br><br>\n";
            }

            foreach($caches as $cacheName => $cacheValues){
                createEntity($cacheName." Cache l&ouml;schen", $cacheName);
            }

            createEntity('Alle l&ouml;schen', 'all');


        ?>
    </div>
</div>
</body>
</html>


