<?php
require_once('auth.php');
require_once('app/config.php');

// help
function has_help_file() {
	global $ne2_config_info;
	$help_file = $ne2_config_info['help_path'] .'update'. $ne2_config_info['help_filesuffix'] ;
	return file_exists($help_file);
}

if(!file_exists("../../".$ne2_config_info['website']) || !file_exists("../../".$ne2_config_info['variables'])){
	header('Location: website_editor.php');
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Live Update - <?php echo($ne2_config_info['app_titleplain']); ?></title>
<link rel="stylesheet" type="text/css" href="css/styles.css?<?php echo date('Ymdis'); ?>" />

<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="js/json2.js"></script>

<script type="text/javascript">
	var sv = "";
	var tv = "";
	var goback = false;

	var helpText = "";

	function check() {
		$("#imgLoading").show();
		$.post("app/live_update.php", {
			"oper": "check_update"
		}, function(rdata) {
			var jdata = JSON.parse(rdata);
			$("#imgLoading").hide();
			if(jdata.error != "") {
				$("#errorMsg").html(jdata.error);
			} else {
				$("#cur_ver").html(jdata.current_version);
				if(jdata.has_stable_update == false) {
					$("#stb_ver").html(jdata.stable_version);
                                        $("#stb_changelog").html(jdata.stable_chlog);
					sv = jdata.stable_version;
					$("#btnDoUpdate").removeAttr("disabled");
					goback = true;
				} else {
					$("#stb_ver").html("<span style='color:red;'>"
                                            + jdata.stable_version
                                            + "</span>");
                                        $("#stb_changelog").html(jdata.stable_chlog);
					sv = jdata.stable_version;
					$("#btnDoUpdate").removeAttr("disabled");
				}

				if(jdata.has_test_update == true) {
					$("#tst_ver").html(jdata.test_version);
                                        $("#tst_changelog").html(jdata.test_chlog);
					tv = jdata.test_version;
					$("#btnDoTestUpdate").removeAttr("disabled");
				}
			}
		});
	}

	$(document).ready(function() {
		$("#btnDoUpdate").click(function() {
			if(goback) {
				if(!confirm("Sind Sie sicher, dass Sie auf eine alte stabile Version wechseln wollen?")) {
					return;
				}
			}
			$("#imgLoading").show();
			$("#btnDoUpdate").attr("disabled", "true");
			$.post("app/live_update.php", {
				"oper": "do_update",
				"uv": sv
			}, function(rdata) {
				$("#imgLoading").hide();
				alert(rdata);
				location.reload();
			});
		});

		$("#btnDoTestUpdate").click(function() {
			if(!confirm("Sind Sie sicher, dass Sie auf eine Testversion wechseln wollen?")) {
				return;
			}
			$("#imgLoading").show();
			$("#btnDoTestUpdate").attr("disabled", "true");
			$.post("app/live_update.php", {
				"oper": "do_update",
				"uv": tv
			}, function(rdata) {
				$("#imgLoading").hide();
				alert(rdata);
				location.reload();
			});
		});

		$("#btnCheckUpdate").click(function() {
			check();
		});
		// help
		$("#helpHand a").click(function() {
			if(helpText == "") {
				$.get("app/get_help.php?r=" + Math.random(), {
					"page_name": "update"
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

<body id="bd_Update" onload="check();">
<div id="wrapper">
	<h1 id="header"><?php echo($ne2_config_info['app_title']); ?></h1>
	<div id="navBar">
		<?php require('common_nav_menu.php'); ?>
	</div>
	<div id="textbereich">

	<?php
	// help
	if(has_help_file()) {
	?>
		<div id="helpCont">.</div>
		<div id="helpHand"><a href="javascript:;">Hilfe</a></div>
	<?php
	}
	?>
		<div id="updateInfo">

                    <h2>Update</h2>
                    <table class="versionsinfo">
                        <tr>
                            <th>Aktuell verwendete Version:</th>
                            <td><span id="cur_ver"><?php echo($ne2_config_info['version']); ?></span></td>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <th>Letzte offizielle Testversion:</th>
                            <td><span id="tst_ver"></span></td>
                            <td><input type="button" id="btnDoTestUpdate" class="button" value="Diese Testversion installieren" disabled="true" /></td>
                            <td><b>Letzte Änderungen:</b> <pre id="tst_changelog" class="changelog"></pre></td>
                        </tr>
                        <tr>
                            <th>Stabile Version</th>
                            <td><span id="stb_ver"></span></td>
                            <td><input type="button" id="btnDoUpdate" class="button" value="Diese Version installieren" disabled="true" /></td>
                            <td><b>Letzte Änderungen:</b>
                                <pre id="stb_changelog" class="changelog"></pre></td>

                        </tr>
                    </table>



		</div>
		<hr />
		<input type="button" id="btnCheckUpdate" class="button" value="Check" />
		<img id="imgLoading" src="ajax-loader.gif" border="0" width="16" height="16" style="display:none;" />
		<div id="errorMsg" style="color:red;"></div>
	</div>

<?php require('common_footer.php'); ?>
</div>
</body>

</html>