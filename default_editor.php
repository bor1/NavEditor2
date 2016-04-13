<?php
require_once('auth.php');
require_once('app/config.php');
require_once('app/classes/BereichsManager.php');


$thiseditor = null;

$BerManager = new BereichsManager();
$alleBereiche = $BerManager->getAllAreaSettings();

foreach ($alleBereiche as $value) {
    if(isset($_GET[$value['name']])){
        $thiseditor = $value;
        break;
    }
}

if (!is_array($thiseditor)){
    echo ("Fehler: Request unbekannt");
    return;
}


// help
function has_help_file() {
	global $ne2_config_info, $thiseditor;
	$help_file = $ne2_config_info['help_path'] .$thiseditor['help_page_name']. $ne2_config_info['help_filesuffix'] ;
	return file_exists($help_file);
}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo ($thiseditor["title"]); ?> bearbeiten - <?php echo($ne2_config_info['app_titleplain']); ?></title>

<?php
    echo NavTools::includeHtml("default",
            "nav_tools.js", //todo put to default?
            "../tiny_mce/tiny_mce.js", //eigentlich sollte in JS verzeichnis sein..
            "json2.js"
            );
?>


<script type="text/javascript">
var tinymceReady = false;

var helpText = "";

tinyMCE.init({
	forced_root_block : '',
	mode: "textareas",
	theme: "advanced",
	language: "de",
	skin: "o2k7",
	relative_urls: false,
	convert_urls: false,
	plugins: "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
	theme_advanced_styles: "infologo",
	theme_advanced_toolbar_location: "top",
	theme_advanced_toolbar_align: "left",
	theme_advanced_statusbar_location: "bottom",
	oninit: function() {
		tinymceReady = true;
	}
});
</script>

<script type="text/javascript">
function loadContentCallback(cdata) {
//	$("#txtContent").val(data);
	var rdata = cdata.replace(/<comment_ssi>/g, "<!-" + "-#");
	rdata = rdata.replace(/<comment>/g, "<!-" + "-");
	rdata = rdata.replace(/<\/comment>/g, "-" + "->");
	tinyMCE.get("txtContent").setContent(rdata);
}

var tinymceReadyCheck = null;
var this_editor_name = '<?php echo $thiseditor['name'];?>';
//var NavTools = new NavTools({something: 5});

function loadContent() {
	if(tinymceReady) {

        NavTools.call_php('app/classes/BereichsEditor.php', 'get_content',
                        {tinymce: true, bereich: this_editor_name},
                        loadContentCallback);

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

            NavTools.call_php('app/classes/BereichsEditor.php', 'update_content',
                        {tinymce: true, bereich: this_editor_name, new_content: cnt},
                        saveContentCallback);
		}
	});

	// help
	$("#helpHand a").click(function() {
		if(helpText == "") {
			$.get("app/get_help.php?r=" + Math.random(), {
				"page_name": "<?php echo ($thiseditor['help_page_name']); ?>"
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

<body id="bd_Inhal">
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
            } else {
                ?>
                <div id="hmmDesignDiesIfNoHelpFileAndNoThisDivDontKnowWhyMaybeLaterSomeoneFixThisLol">&nbsp;</div>
            <?php } ?>
            <form action="" method="post" name="frmEdit" id="frmEdit">
                <fieldset>
                    <legend><?php echo ($thiseditor["title"]); ?> bearbeiten</legend>
                    <label for="txtContent">Inhalt:</label>
                    <textarea id="txtContent" name="txtContent" cols="160" rows="25" class="textBox"></textarea>
                    <hr size="1" noshade="noshade" />
                    <input type="button" id="btnUpdate" name="btnUpdate" value="Update" class="button" />
                </fieldset>
            </form>
        </div>

        <?php require('common_footer.php'); ?>
    </div>
</body>

</html>