<?php
require_once('auth.php');
require_once('app/config.php');

if(!$is_admin) { // this page is for admin only!
	auth401();
}

// help
function has_help_file() {
	global $ne2_config_info;
	$help_file = $ne2_config_info['help_path'] .'fuss_editor'. $ne2_config_info['help_filesuffix'] ;
	return file_exists($help_file);
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Fu&szlig;text bearbeiten - <?php echo($ne2_config_info['app_titleplain']); ?></title>
<link rel="stylesheet" type="text/css" href="css/styles.css?<?php echo date('Ymdis'); ?>" />

<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
var tinymceReady = false;

var helpText = "";

tinyMCE.init({
	forced_root_block : '',
	mode: "textareas",
	language: "de",
	theme: "advanced",
	relative_urls: false,
	convert_urls: false,
	skin: "o2k7",
	plugins: "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
	theme_advanced_toolbar_location: "top",
	theme_advanced_toolbar_align: "left",
	theme_advanced_statusbar_location: "bottom",
	oninit: function() {
		tinymceReady = true;
	}
});
</script>

<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="js/json2.js"></script>
<script type="text/javascript">
function loadContentCallback(cdata) {
//	$("#txtContent").val(data);
	var rdata = cdata.replace(/<comment_ssi>/g, "<!-" + "-#");
	rdata = rdata.replace(/<comment>/g, "<!-" + "-");
	rdata = rdata.replace(/<\/comment>/g, "-" + "->");
	tinyMCE.get("txtContent").setContent(rdata);
}

var tinymceReadyCheck = null;

function loadContent() {
	if(tinymceReady) {
		$.get("app/edit_fuss.php?r=" + Math.random(), {
			"json_oper": "get_content"
		}, loadContentCallback); // load tree data
		
		if(tinymceReadyCheck !== null) {
			clearTimeout(tinymceReadyCheck);
		}
	} else {
		setTimeout("loadContent()", 500);
	}
}

function saveContentCallback(data) {
	alert(data);
	location.reload();
}

/* ---------- Here comes jQuery: ---------- */
$(document).ready(function() {
	loadContent();
	
	$("#btnUpdate").click(function() {
		if(confirm("Sind Sie sicher?")) {
			var cnt = tinyMCE.get("txtContent").getContent();
			cnt = cnt.replace(new RegExp("<!-" + "-#", "g"), "<comment_ssi>");
			cnt = cnt.replace(/<!--/g, "<comment>");
			cnt = cnt.replace(/-->/g, "</comment>");
			$.post("app/edit_fuss.php", {
				"json_oper": "update_content",
				"content_html": cnt
			}, saveContentCallback);
		}
	});

	// help
	$("#helpHand a").click(function() {
		if(helpText == "") {
			$.get("app/get_help.php?r=" + Math.random(), {
				"page_name": "fuss_editor"
			}, function(rdata){
				helpText = rdata;
				$("#helpCont").html(helpText);
				$("#helpCont").slideToggle("fast");
			});
		} else {
			$("#helpCont").slideToggle("fast");
		}
	});
});
</script>
</head>

<body id="bd_Fuss">
<div id="wrapper">
	<h1 id="header"><?php echo($ne2_config_info['app_title']); ?></h1>
	<div id="navBar">
		<?php require('common_nav_menu.php'); ?>
	</div>
	
	<div id="contentPanel1">
	<?php
	// help
	if(has_help_file()) {
	?>
		<div id="helpCont">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</div>
		<div id="helpHand"><a href="javascript:;">Hilfe</a></div>
	<?php
	}
	?>
		<form action="" method="post" name="frmEdit" id="frmEdit">
			<fieldset>
				<legend>Fu&szlig;text bearbeiten</legend>
				<label for="txtContent">Inhalt:</label>
				<textarea id="txtContent" name="txtContent" cols="160" rows="25" class="textBox"></textarea>
				<p>
				<input type="button" id="btnUpdate" name="btnUpdate" value="Update" class="button" />
				</p>
			</fieldset>
		</form>
	</div>
	
<?php require('common_footer.php'); ?>	
</div>
</body>

</html>
