<?php
require_once('auth.php');
require_once('app/config.php');


// help
function has_help_file() {
	global $ne2_config_info;
	$help_file = $ne2_config_info['help_path'] .'design_editor'. $ne2_config_info['help_filesuffix'] ;
	return file_exists($help_file);
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Design wechseln - <?php echo($ne2_config_info['app_titleplain']); ?></title>
<link rel="stylesheet" type="text/css" href="css/styles.css?<?php echo date('Ymdis'); ?>" />

<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="js/json2.js"></script>
<script type="text/javascript" src="js/loading.js"></script>
<script type="text/javascript">
var loadFileListDone = false;

var helpText = "";

function loadContentCallback(data) {
        if(!data){alert('no designs found');return;}
	var curr = data.current_design;
	var optHtml = "";
	for(var i = 0; i < data.designs.length; i++) {
		if(data.designs[i].value == curr) {
			optHtml += "<option value=\"" + data.designs[i].value + "\" selected=\"selected\">" + data.designs[i].text + "</option>";
			loadChkboxes(data.designs[i].value);
		} else {
			optHtml += "<option value=\"" + data.designs[i].value + "\">" + data.designs[i].text + "</option>";
		}
	}
	$("#selDesigns").html(optHtml);
	loadFileListDone = true;
	showScreenShot($("#selDesigns").val());
	$('#confDesignLegend').html('Design '+ curr +" konfigurieren");
}

function saveContentCallback(data) {
	alert(data);
}

function showScreenShot(fileName) {
	$.post("app/edit_design.php", {
		"oper": "get_screenshot",
		"head_file_name": fileName
	}, function(rdata) {
		$("#previewImage").html(rdata);
	});
}

function decode_utf8(s){
	return decodeURIComponent(escape(s));
}
//get design settings
function loadChkboxes(design){
		$.getJSON("app/edit_design.php", {
		"oper": "get_settings",
		"head_file_name": design
		}, function(rdata) {
			var din_chkboxes_html = "";
                        if(!rdata){
                            din_chkboxes_html = "no setting found";
                        }else{
                            for(var i = 0; i < rdata.length; i++){
                                    din_chkboxes_html += "<input type='checkbox' id='"+ rdata[i].setting +"' value='" + rdata[i].setting +"' "+ (rdata[i].checked ? "checked='checked'" : "") +" /><label for='"+ rdata[i].setting +"'>  "+ decode_utf8(rdata[i].setting_descr) +"</label><br>";
                            }
                        }
			$('#settingsBlock').html(din_chkboxes_html);
		});
}

/* ---------- Here comes jQuery: ---------- */
$(document).ready(function() {
	$.getJSON("app/edit_design.php?r=" + Math.random(), {
		"oper": "get_file_list"
	}, loadContentCallback); // load tree data

	$("#btnUpdate").click(function() {
		if(confirm("Are you sure to change the Design?")) {
			$.post("app/edit_design.php", {
				"oper": "set_head_file",
				"new_head_file": $("#selDesigns").val()
			}, saveContentCallback);
		}
	});

	$("#selDesigns").change(function() {
		if(!loadFileListDone) return;
		this_selected = $(this).val();
		showScreenShot(this_selected);
		$('#confDesignLegend').html('Design '+ this_selected +" konfigurieren");
		loadChkboxes($("#selDesigns").val());
	});



	// set design settings
	$("#btnUpdKopf").click(function() {
		var checks = $("#frmKopf [type='checkbox']");
		var settings = {};
		if(checks.length > 0) {
			for(var i = 0; i < checks.length; i++) {
				if($(checks[i]).attr('checked')){
					settings[$(checks[i]).val()] = true;
				}else{
					settings[$(checks[i]).val()] = false;
				}
			}
                        $.post("app/edit_design.php", {
                                "oper": "set_settings",
                                "head_file_name": $("#selDesigns").val(),
                                "settings": JSON.stringify(settings)
                        },function(rdata) {
                                alert(rdata);
                        });
                }
	});

	// help
	$("#helpHand a").click(function() {
		if(helpText == "") {
			$.get("app/get_help.php?r=" + Math.random(), {
				"page_name": "design_editor"
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

<body id="bd_Design">
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
		<div id="helpCont">.</div>
		<div id="helpHand"><a href="javascript:;">Hilfe</a></div>
	<?php
	}
	?>
		<form action="" method="post" name="frmEdit" id="frmEdit">
			<fieldset>
				<legend>Designs ausw&auml;hlen</legend>
				<select id="selDesigns"></select>
				<input type="button" id="btnUpdate" name="btnUpdate" value="Dieses Design aktivieren" class="button" />
				<div id="previewImage" style="padding:0.25em 0 0 0;"></div>
				<hr size="1" noshade="noshade" />
			</fieldset>
		</form>

		<form id="frmKopf">
			<fieldset>
				<legend id="confDesignLegend">Design Konfigurieren</legend>
				<div id="settingsBlock">
				<!--<input type="checkbox" id="chkZielGrpNav" value="mitZiel" checked="checked" /> <label for="chkZielGrpNav">Zielgruppennavigation</label>
				<input type="checkbox" id="chkSuche" value="mitSuche" checked="checked" /> <label for="chkSuche">Suche</label>-->
				</div>
				<input type="button" id="btnUpdKopf" name="btnUpdKopf" class="button" value="Einstellungen speichern" />
			</fieldset>
		</form>
	</div>

<?php require('common_footer.php'); ?>
</div>
</body>

</html>
