<?php
require_once('app/config.php');
require_once('auth.php');
require_once('app/classes/SimplePie.php');


$feedUrl = $ne2_config_info['dashboard_feed'];

// call simplepie... get feed info... error handling?
set_time_limit(15);

$sp = new SimplePie();
$sp->strip_htmltags(array('base', 'blink', 'body', 'doctype', 'font', 'form', 'frame', 'frameset', 'html', 'iframe', 'input', 'marquee', 'meta', 'noscript', 'style'));
$sp->strip_attributes(array('bgsound', 'class', 'expr', 'id', 'onclick', 'onerror', 'onfinish', 'onmouseover', 'onmouseout', 'onfocus', 'onblur', 'lowsrc', 'dynsrc'));
$sp->set_cache_location('./data');
$sp->set_feed_url($feedUrl);
$sp->set_timeout(15);
$sp->init();
$sp->handle_content_type();

if($sp->error()) {
	echo('[ERR] ' . $sp->error() . '<br />');
	$sp->__destruct();
	unset($sp);
	return FALSE;
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Dashboard - <?php echo($ne2_config_info['app_titleplain']); ?></title>
<link rel="stylesheet" type="text/css" href="css/styles.css?<?php echo date('Ymdis'); ?>" />
<link href="css/jquery-ui-1.8.2.custom.css" media="screen, projection" type="text/css" rel="stylesheet" />
<script src="js/jquery-1.4.2.min.js" type="text/javascript"></script>
<script src="js/jquery-ui-1.8.2.custom.js" type="text/javascript"></script>
<script type="text/javascript" src="js/jquery.ui.accordion.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		$("#acc").accordion({
			active: false,
			collapsible: true,
			navigation: true,
			autoHeight: false,
			icons: {
				'header': 'ui-icon-plus',
				'headerSelected': 'ui-icon-minus'
			}
		});
	});
</script>
</head>

<body id="bd_Dash">
<div id="wrapper">
	<h1 id="header"><?php echo($ne2_config_info['app_title']); ?></h1>
	<div id="navBar">
		<?php require('common_nav_menu.php'); ?>
	</div>
	
	<div id="contentPanel1">
		<h2 id="dashbrd_title"><a href="<?php echo($sp->get_permalink()); ?>"><?php echo($sp->get_title()); ?></a></h2>
<div id="acc">
<?php foreach($sp->get_items() as $item) { ?>
	<h3><a href="<?php echo $item->get_permalink(); ?>"><?php echo $item->get_title(); ?></a></h3>
	<div class="fc">
		<p class="ftime"><?php echo $item->get_date('j F Y | g:i a'); ?></p>
		<div class="fcontent"><?php echo $item->get_description(); ?></div>
	</div>
<?php } ?>
</div>
	</div>
	
<?php require('common_footer.php'); ?>	
</div>
</body>

</html>
