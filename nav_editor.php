<?php
require_once('auth.php');
require_once('app/config.php');


// help
function has_help_file() {
	global $ne2_config_info;
	$help_file = $ne2_config_info['help_path'] .'nav_editor'. $ne2_config_info['help_filesuffix'] ;
	return file_exists($help_file);
}

// after an update, when first run, check and clean temp files (.buffer, .lock, .buffer.lock)
function removeTempFiles($dir) {
	if(is_dir($dir)) {
		if($dh = opendir($dir)) {
			while(FALSE !== ($file = readdir($dh))) {
				// escaped dirs
				if($file != '.' && $file != '..' && $file != 'css' && $file != 'grafiken' && $file != 'img' && $file != 'Smarty' && $file != 'ssi' && $file != 'univis' && $file != 'vkapp' && $file != 'vkdaten' && $file != 'xampp') {
					if(is_dir($dir . '/' . $file)) {
						// recursively
						removeTempFiles($dir . '/' . $file);
					} else {
						$lf = $dir . '/' . $file . '.lock';
						if(file_exists($lf)) {
							@unlink($lf);
						}

						$bf = $dir . '/' . $file . '.buffer';
						if(file_exists($bf)) {
							@unlink($bf);
						}
					}
				}
			}
			closedir($dh);
		}
	}
}

// prepare for the first run!
$updated_mark_file = $ne2_config_info['app_path'] . 'data/just_updated.txt';
// check for template existence

$templ_path_sv0 = $ne2_config_info['template_path'] . '_'. $ne2_config_info['template_default'];
$templ_path_sv1 = $ne2_config_info['template_path'] . $ne2_config_info['template_default'];


if(!file_exists($templ_path_sv1)) {
	copy($templ_path_sv0, $templ_path_sv1);
}
// delete old buffer/lock files
if(file_exists($updated_mark_file)) {
	removeTempFiles($_SERVER['DOCUMENT_ROOT']);
	unlink($updated_mark_file);
}
//

$dirty_indicator = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/navindex.dirty';
if(file_exists($dirty_indicator)) {
	$is_dirty = 'true';
} else {
	$is_dirty = 'false';
}

$custom_css_classes = '';
if($ne2_config_info['custom_content_css_classes'] != '') {
	$custom_css_classes = array();
	$arr_cls = explode('|', $ne2_config_info['custom_content_css_classes']);
	foreach($arr_cls as $ac) {
		array_push($custom_css_classes, $ac . '=' . $ac);
	}
	$custom_css_classes = implode(';', $custom_css_classes);
}

$navtree_start_open = 'true';
if($ne2_config_info['navtree_start_open'] == '0') {
	$navtree_start_open = 'false';
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Seiten bearbeiten - <?php echo($ne2_config_info['app_titleplain']); ?></title>
<link rel="stylesheet" type="text/css" href="css/styles.css?<?php echo date('Ymdis'); ?>" />
<?php if($ne2_config_info['show_navtree_numbers'] == '0') { ?>
<style type="text/css">
#dirTreePanel li em.treeKey {
	display: none;
}
</style>
<?php } ?>
<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
<!--
<script type="text/javascript" src="js/jquery.rightClick.js"></script>-->
<script type="text/javascript" src="js/json2.js"></script>
<script type="text/javascript" src="js/naveditor2.js"></script>
<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
var g_tiny_isDirty = false;


// Creates a new plugin class and a custom listbox
tinymce.create('tinymce.plugins.VarsPlugin', {
    createControl: function(n, cm) {
        switch (n) {
            case 'varslb':
                var mlb = cm.createListBox('varslb', {
                     title : 'Variablen',
                     onselect : function(v) {
                         tinyMCE.activeEditor.focus();
						 tinyMCE.activeEditor.selection.setContent(v);
                     }
                });

                // jquery -> json config vars von variables.conf holen, und dynamisch listbox erstellen
					$.getJSON("app/edit_conf.php?r=" + Math.random(), {
						"oper": "get_conf",
						"conf_file_name": '<?php echo($ne2_config_info['variables']); ?>'
					}, function(rdata){
						for(var i = 0; i < rdata.length; i++) {
							var ciName = rdata[i].opt_name;
							var ciValue = rdata[i].opt_value;
							mlb.add(ciName, ciValue);
						}
					});
					// mlb.add('var1', 'wert'));

                // Return the new listbox instance
                return mlb;
        }

        return null;
    }
});
// Register plugin with a short name
tinymce.PluginManager.add('variables', tinymce.plugins.VarsPlugin);


tinyMCE.init({
	mode: "textareas",
	theme: "advanced",
	skin: "o2k7",
	language: "de",
	forced_root_block : '',
	plugins: "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template, mitarbeiter, feedimport, -variables",
	relative_urls: false,
	convert_urls: false,

	theme_advanced_buttons1: "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,styleselect,|,bullist,numlist,outdent,indent,blockquote,sub,sup,|,cut,copy,paste,pastetext,pasteword,|,search,replace,|,undo,redo",
	theme_advanced_buttons2: "tablecontrols,|,link,unlink,anchor,image,cleanup,|,hr,removeformat,visualaid,|,charmap,emotions,media,iespell,|,ltr,rtl,|,fullscreen,help,code,|,nachOben, | ,mitarbeiter, feedimport, varslb",
	theme_advanced_buttons3: "",

	theme_advanced_toolbar_location: "top",
	theme_advanced_toolbar_align: "left",
	theme_advanced_statusbar_location: "bottom",
	theme_advanced_blockformats: "p,address,pre,h2,h3,h4,h5,h6,blockquote,code", // p,address,pre,h1,h2,h3,h4,h5,h6
	theme_advanced_styles: "<?php echo($custom_css_classes); ?>",
	entity_encoding: "raw",
	setup: function(ed) {
		ed.onKeyDown.add(function(ed, e) {
			g_tiny_isDirty = true;
		});
		// add a custom button
		ed.addButton("nachOben", {
			title: "Nach oben Link einfuegen",
			image: "/vkdaten/tools/NavEditor2/css/example.gif",
			onclick: function() {
				ed.focus();
				ed.selection.setContent("<p class='noprint'><a href='#seitenmarke'>Nach oben</a></p>");
			}
		});
	}
});
</script>

<script type="text/javascript">
<!--
var g_treeIsDirty = eval("<?php echo($is_dirty); ?>");
var g_newKey = "";
var g_hasFocus = false;
var gUserPermissionPath = "<?php echo($g_current_user_permission); ?>";
var gUserPermissionPaths = gUserPermissionPath.split("|");
var folderTreeData = null;
var currentSelectedNode = null;
var navTreeStartOpen = <?php echo($navtree_start_open); ?>;
var g_headsLoaded = false;
var helpText = "";
var g_currentSelectedNodePath = "";
var g_currentEditingContent = {
	"title": "",
	"alias": "",
	"url": "",
	"info_text": "",
	"title_icon": "",
	"title_icon_alt": "",
	"title_icon_title": "",
	"title_display": "",
	"content": ""
};

// cache current editing content for tree refresh
function saveCurrentEditingContent() {
	var ttl = $("#txtTitle").val();
	var als = $("#txtAlias").val();
	var url = $("#txtUrl").val();
	var ift = $("#txtInfotext").val();
	var tti = $("#txtIcon").val();
	var ttia = $("#txtIconAlt").val();
	var ttit = $("#txtIconTitle").val();
	var ttdis = $("#txtTitleDisplay").val();
	var cnt = tinyMCE.get("txtContent").getContent();
	g_currentEditingContent.title = ttl;
	g_currentEditingContent.alias = als;
	g_currentEditingContent.url = url;
	g_currentEditingContent.info_text = ift;
	g_currentEditingContent.display_link = $("#chkDisplayLink:checked").length > 0 ? true : false;
	g_currentEditingContent.title_icon = tti;
	g_currentEditingContent.title_icon_alt = ttia;
	g_currentEditingContent.title_icon_title = ttit;
	g_currentEditingContent.title_display = ttdis;
	g_currentEditingContent.content = cnt;
}

function loadCurrentEditingContent() {
	$("#txtTitle").val(g_currentEditingContent.title);
	$("#txtAlias").val(g_currentEditingContent.alias);
	$("#txtUrl").val(g_currentEditingContent.url);
	$("#txtInfotext").val(g_currentEditingContent.info_text);
	$("#txtIcon").val(g_currentEditingContent.title_icon);
	$("#txtIconAlt").val(g_currentEditingContent.title_icon_alt);
	$("#txtIconTitle").val(g_currentEditingContent.title_icon_title);
	$("#txtTitleDisplay").val(g_currentEditingContent.title_display);
	tinyMCE.get("txtContent").setContent(g_currentEditingContent.content);
}

function foldNavTree() {
	if(!navTreeStartOpen) {
		// fold all
		$("#" + navTreeOper.dirHtmlPanelId + " li b.tg").html("+");
		$("#" + navTreeOper.dirHtmlPanelId + " li ul").hide();
		// highlighted element unfold TODO
		if(g_currentSelectedNodePath != "") {
			$("#" + navTreeOper.dirHtmlPanelId + " li[rel='" + g_currentSelectedNodePath + "']").parents("ul").show();
			$("#" + navTreeOper.dirHtmlPanelId + " li[rel='" + g_currentSelectedNodePath + "']").parents("li").find("b.tg").html("-");
		}
	}
}

function getCurrentPermission() {
	var currentPath = navTreeOper.getPathInfo(navTreeOper.getCurrentNode());
	for(var i = 0; i < gUserPermissionPaths.length; i++) {
		if(gUserPermissionPaths[i] == "/") {
			return "Sibling-Child";
		}
		if(currentPath.indexOf(gUserPermissionPaths[i]) >= 0) {
			if(currentPath == gUserPermissionPaths[i]) {
				return "Child";
			} else {
				return "Sibling-Child";
			}
		} else {
			continue;
		}
	}
	return "No";
}

function makeTreeDirty(dirty) {
	g_treeIsDirty = dirty;
	if(g_treeIsDirty) {
		$("#btnPublishTree").removeAttr("disabled");
	} else {
		$("#btnPublishTree").attr("disabled", "true");
	}
}

function drawPermission() {
	// check permission to colorize navTree
	if(gUserPermissionPath == "/") {
		$("#pnlNormal li").css("color", "#000");
	} else {
                var relTmp = "";
		for(var i = 0; i < gUserPermissionPaths.length; i++) {
                    //falls permission auf "/" eingestellt ist, dann als /index.shtml betrachten, damit farb markirung funkt-rt
                    if(gUserPermissionPaths[i].substr(gUserPermissionPaths[i].length-1, 1) == "/" ){
                        relTmp = gUserPermissionPaths[i] + "index.shtml";
                    }else{
                        relTmp = gUserPermissionPaths[i];
                    }
			$("#pnlNormal li[rel='" + relTmp + "']").css("color", "#000");
			$("#pnlNormal li[rel='" + relTmp + "'] li").css("color", "#000");
		}
	}
}


function loadTreeCallback(jsonTreeData) {
	navTreeOper.refreshNavTree(jsonTreeData);
	resetTextBox();
	drawPermission();
	folderTreeData = navTreeOper._cloneObject(jsonTreeData.A);
	navTreeOper.addPathInfo(folderTreeData);
	buildFolderTree(folderTreeData);
	$("#selFolders").html(_navTreeOptString);
	repaintFolderTree();

	// initially show / hide child navTree nodes
	foldNavTree();

	// load tree backups
	loadNavTreeBackupList();

	// temporarily disable editing area
	promptForClickingNode();

	<?php
	//falls mit der GET methode ein pfad uebergeben wird
	if(isset($_GET['path'])){
		$pathForJs = urldecode($_GET['path']);
		print "
			treeNodeClickEx('$pathForJs');
			treeNodeClick(navTreeOper.getCurrentNode().key);
			foldNavTree();";
	}
	?>
}

function loadNavTreeBackupList() {
	$.post('app/update_contents.php', {
		"json_oper": "get_navindex_backup_list",
		"json_data": ""
	}, function(rdata) {
		var jdata = JSON.parse(rdata);
                var fileparts;
                var jahr, monat, tag, stunde, minute;

		opt = "<option value=\"0\" selected=\"selected\">-- Backup-Liste --</option>";
		for(var i = 0; i < jdata.length; i++) {
		//	opt += "<option value=\"" + jdata[i].file_name + "\">" + jdata[i].file_name + "</option>";


                        fileparts = jdata[i].file_name.split(".");
                        jahr = fileparts[0].substr(0,4);
                        monat = fileparts[0].substr(4,2);
                        tag = fileparts[0].substr(6,2);
                        stunde = fileparts[0].substr(8,2);
                        minute = fileparts[0].substr(10,2);
                        opt += "<option value=\"" + jdata[i].file_name + "\">"
                                + tag + "." + monat + "." + jahr + ", "
                                + stunde + ":" + minute
                                + " Uhr </option>";

		}
		$("#selNavIdxBackupList").empty();
		$("#selNavIdxBackupList").append(opt);
	});
}

function getContentCallback(data) {
	var jdata = JSON.parse(data);
	var cdata = jdata.content_html;
	var isDraft = jdata.is_draft;
	var isLocked = jdata.is_locked;
	if(isLocked) {
		alert("Die Seite wird gerade bearbeitet und ist daher gesperrt.");
		// hide buttons
		$("#btnSaveDraft").hide();
		$("#btnUpdate").hide();
		$("#lockPrompt").html("<b>Die Seite wird gerade bearbeitet und ist daher gesperrt.</b>");
	} else {
		// show buttons
		$("#btnSaveDraft").show();
		$("#btnUpdate").show();
		$("#lockPrompt").html("");
	}
	// server ssi tag workaround
	var rdata = cdata.replace(/<comment_ssi>/g, "<!-" + "-#");
	rdata = rdata.replace(/<comment>/g, "<!-" + "-");
	rdata = rdata.replace(/<\/comment>/g, "-" + "->");

	tinyMCE.get("txtContent").setContent(rdata);
	g_tiny_isDirty = false;

	if(isDraft) {
		$("#draftPrompt").html("Seite ist noch nicht publiziert.");
	} else {
		$("#draftPrompt").html("");
	}
}

function getBackupListCallback(data) {
	var jdata = JSON.parse(data);

	opt = "<option value=\"0\" selected=\"selected\">-- Backup-Liste --</option>";

        var fileparts;
        var jahr, monat, tag, stunde, minute;
        var entries;
	for(var i = 0; i < jdata.length; i++) {
                fileparts = jdata[i].file_name.split("_");
                jahr = fileparts[1].substr(0,4);
                monat = fileparts[1].substr(4,2);
                tag = fileparts[1].substr(6,2);
                stunde = fileparts[1].substr(8,2);
                minute = fileparts[1].substr(10,2);
    		opt += "<option value=\"" + jdata[i].file_name + "\">"
                        + tag + "." + monat + "." + jahr + ", "
                        + stunde + ":" + minute
                        + " Uhr </option>";
	}

        $("#selOldVersions").empty();
        $("#selOldVersions").append(opt);

}

function treeNodeClickEx(nodePath) {
	$("#" + navTreeOper.dirHtmlPanelId + " ul li span").removeClass("currentSelected");
	$("#" + navTreeOper.dirHtmlPanelId + " ul li[rel='" + nodePath + "'] > span").addClass("currentSelected");
	var cKey = $("#" + navTreeOper.dirHtmlPanelId + " ul li[rel='" + nodePath + "'] > span").attr("id");
//	alert(cKey);
	navTreeOper.setCurrentKey(cKey);

	var cNode = navTreeOper.locateNode(cKey);
	navTreeOper.setCurrentNode(cNode);
	currentSelectedNode = cNode;

	if(navTreeOper.getCurrentNode() == null) {
		alert("ERROR: Current-Node is null!");
		return;
	}

	var _path = navTreeOper.getPathInfo(cNode);
	cNode.path = _path;

	navTreeOper.updateButtons();
}

function treeNodeClick(nodeKey) {
	$("#" + navTreeOper.dirHtmlPanelId + " ul li span").removeClass("currentSelected");
	$("#" + nodeKey).addClass("currentSelected"); // highlight span

	var cKey = nodeKey;
	navTreeOper.setCurrentKey(cKey);

	var cNode = navTreeOper.locateNode(cKey);
	navTreeOper.setCurrentNode(cNode);
	currentSelectedNode = cNode;

	if(navTreeOper.getCurrentNode() == null) {
		alert("ERROR: Current-Node is null!");
		return;
	}

	unpromptForClickingNode();

	// update textboxs
	$("#txtTitle").attr("value", cNode.title);
	$("#txtAlias").attr("value", cNode.alias);
	$("#txtUrl").attr("value", cNode.url);
	$("#txtInfotext").attr("value", cNode.info_text);


	$("#txtIcon").val(cNode.title_icon);
	$("#txtIconAlt").val(cNode.title_icon_alt);
	$("#txtIconTitle").val(cNode.title_icon_title);
	$("#txtTitleDisplay").val(cNode.title_display);

	var _path = navTreeOper.getPathInfo(cNode);
	cNode.path = _path;
	$.post("app/update_contents.php?r=" + Math.random(), {
		"json_oper": "get_content",
		"json_data": JSON.stringify(cNode)
	}, getContentCallback);

	// get backup file list
	$.post("app/update_contents.php?r=" + Math.random(), {
		"json_oper": "get_backup_list",
		"json_data": JSON.stringify(cNode)
	}, getBackupListCallback);

	navTreeOper.updateButtons();

	$("#" + navTreeOper.debugInfoPanelId).text(navTreeOper._currentKey + ": " + navTreeOper._currentNode.title + "; " + navTreeOper._currentNode.path);
	// preview link
	$("#lnkPreview").attr("target", "_blank");
	$("#lnkPreview").attr("href", cNode.path);

	g_hasFocus = true;

	repaintFolderTree();

	// set current path
	g_currentSelectedNodePath = _path;
}

function doRecoveryCallback(data) {
	var rdata = data.split("_");
	alert(rdata[0]);
	treeNodeClick(rdata[1]);
}

function doRecovery() {
	var bak_file_name = $("#selOldVersions").val();
	if(bak_file_name != "0") {
		var cNode = navTreeOper.getCurrentNode();
		cNode.path = navTreeOper.getPathInfo(cNode);
		$.post("app/update_contents.php?r=" + Math.random(), {
			"json_oper": "recover",
			"json_data": JSON.stringify(cNode),
			"backup_file_name": bak_file_name
		}, doRecoveryCallback);
	}
}

function registerDirNodeEvents() {
	$("#" + navTreeOper.dirHtmlPanelId + " ul li span").click(function() {
		// check if editor dirty
		if(g_tiny_isDirty) {
			if(!confirm("Sie haben die Seite geändert, aber noch nicht gespeichert.\nSind Sie sicher, dass Sie die Seite verlassen wollen ohne zu speichern?")) {
				return;
			}
		}

		var ckey = $(this).attr("id");
		treeNodeClick(ckey);
	});



	$("#" + navTreeOper.dirHtmlPanelId + " li b.tg").click(function() {
		var btag = $(this);
		var btagFace = btag.html();
		btag.parent().find("> ul").toggle("normal", function() {
			btag.html((btagFace == "-" ? "+" : "-"));
		});
	});
}



function saveTreeDataCallback(data) {
	alert(data);
	g_hasFocus = false;
	makeTreeDirty(false);
}

function resetTextBox() {
	$("#txtTitle").attr("value", "");
	$("#txtAlias").attr("value", "");
	$("#txtContent").val("");
	if(tinyMCE.get("txtContent")) {
		tinyMCE.get("txtContent").setContent("");
		tinyMCE.get("txtContent").isNotDirty = true;
	}
	$("#txtUrl").attr("value", "");
	$("#txtInfotext").attr("value", "");
	$("#txtIcon").val("");
	$("#txtIconAlt").val("");
	$("#txtIconTitle").val("");
	$("#txtTitleDisplay").val("");

	$("#lnkPreview").attr("target", "_self");
	$("#lnkPreview").attr("href", "javascript:;");
}

function promptForClickingNode() {
	$("#txtTitle").attr("disabled", "disabled");
	tinyMCE.get("txtContent").hide();
	$("#txtContent").attr("disabled", "disabled");
	$("#txtContent").val("Bitte wählen Sie zunächst die zu bearbeitende Seite in der Navigation links aus.");
}

function unpromptForClickingNode() {
	$("#txtTitle").removeAttr("disabled");
	$("#txtContent").val("");
	$("#txtContent").removeAttr("disabled");
	tinyMCE.get("txtContent").show();
}

function addNodeCallback(data) {
	if(data == "ERR_NO_PERM") {
		alert("Permission denied!");
		return;
	}

	alert(data);
	resetTextBox();

	drawPermission();

	if(g_newKey != "") {
		treeNodeClick(g_newKey);
	}

	foldNavTree();

	btnSaveTreeClick();

	rebuildFolderTree((navTreeOper.getJSONObject()).A);
	repaintFolderTree();

	makeTreeDirty(true);
}

function removeCallback(data) {
	if(data == "ERR_NO_PERM") {
		alert("Permission denied!");
		return;
	}

	alert(data);
	resetTextBox();

	drawPermission();

	foldNavTree();

	btnSaveTreeClick();
	g_hasFocus = false;

	rebuildFolderTree(navTreeOper.getJSONObject().A);
	repaintFolderTree();

	makeTreeDirty(true);
}

function updateContentsCallback(data) {
	if(data == "ERR_NO_PERM") {
		alert("Permission denied!");
		return;
	}

	alert(data);

	navTreeOper.refreshNavTree(navTreeOper.getJSONObject());
	resetTextBox();

	drawPermission();

	if(g_newKey != "") {
		treeNodeClick(g_newKey);
	}

	foldNavTree();

	btnSaveTreeClick();
}

function btnStartClick() {
	$.getJSON("app/load_tree_data.php?r=" + Math.random(), loadTreeCallback); // load tree data
}

// silent refresh?
function btnStartClickEx(funk) {
	$.getJSON("app/load_tree_data.php?r=" + Math.random(), function(jsonTreeData) {
		// save current work
		saveCurrentEditingContent();

		navTreeOper.refreshNavTree(jsonTreeData);
		drawPermission();
		folderTreeData = navTreeOper._cloneObject(jsonTreeData.A);
		navTreeOper.addPathInfo(folderTreeData);
		rebuildFolderTree(folderTreeData);

		// initially show / hide child navTree nodes
		foldNavTree();

		if(g_currentSelectedNodePath != "") {
			treeNodeClickEx(g_currentSelectedNodePath);
			loadCurrentEditingContent();
		}

		if(funk) funk();
	});
}

function btnSaveTreeClick() {
	var normalNodes = (navTreeOper.getJSONObject()).A;
	navTreeOper.addPathInfo(normalNodes);
	$.post("app/save_tree_data.php", {
		"publishTree": "Nein",
		"jsonTreeData": JSON.stringify(navTreeOper.getJSONObject())
	});

	folderTreeData = navTreeOper._cloneObject(normalNodes);
	navTreeOper.addPathInfo(folderTreeData);
	rebuildFolderTree(folderTreeData);

	makeTreeDirty(true);
}

function publishTree() {
	var normalNodes = (navTreeOper.getJSONObject()).A;
	navTreeOper.addPathInfo(normalNodes);
	$.post("app/save_tree_data.php", {
		"publishTree": "Ja",
		"jsonTreeData": JSON.stringify(navTreeOper.getJSONObject())
	}, saveTreeDataCallback);
}

function filter(source) {
	var fname = source;
	fname = fname.toLowerCase();

	fname = fname.replace(/\u00df/, "ss");
	fname = fname.replace(/\u00e4/, "ae");
	fname = fname.replace(/\u00f6/, "oe");
	fname = fname.replace(/\u00fc/, "ue");

	fname = fname.replace(/\(.*\)/, ""); // remove parentheses
	fname = fname.replace(/^\s+|\s+$/g, ""); // trim
	fname = fname.replace(/([^\w.\-_])/g, "-"); // spaces to -
	fname = fname.replace(/-{2,}/, "-");

	return fname;
}

function makeIndent(mal) {
	var r = "";
	if(mal <= 0) {
		return "";
	} else {
		for(var i = 0; i < mal; i++) {
			r += "&nbsp; &nbsp; ";
		}
		return r;
	}
}

function levelDiff(k1, k2) {
	ak1 = k1.split("-");
	ak2 = k2.split("-");
	return Math.abs(ak1.length - ak2.length);
}

function folderTreeNodeDropable(folderNodeKey) {
	if(currentSelectedNode == null) {
		return false;
	} else {
		var tkey = currentSelectedNode.key;
		if(levelDiff(tkey, folderNodeKey) <= 1 && tkey.indexOf(folderNodeKey) >= 0) {
			return false;
		}
		if(folderNodeKey.indexOf(tkey) >= 0) {
			return false;
		}
		if(tkey.indexOf("-") < 0 && folderNodeKey == "U") {
			return false;
		}
		return true;
	}
}

var _navTreeOptString = "<option value='U_/'>-- ROOT --</option>";
var _indent = -1;

function rebuildFolderTree(jsonData) {
	_navTreeOptString = "<option value='U_/'>-- ROOT --</option>";
	_indent = -1;
	buildFolderTree(jsonData);
	$("#selFolders").html(_navTreeOptString);
	repaintFolderTree();
}

// build folder-only navtree instance
function buildFolderTree(jsonData) {
	if(jsonData.length < 1) {
		return;
	}
	_indent++;
	for(var i = 0; i < jsonData.length; i++) {
	//display all available sites; but Startseite!
	//	if(jsonData[i].child.length > 0) { // displaysonly folders
			_navTreeOptString += "<option value='" + jsonData[i].key + "_" + jsonData[i].path + "'>" + makeIndent(_indent) + jsonData[i].title + "</option>";
			buildFolderTree(jsonData[i].child); // recursive
	//	}
	}
	_indent--;
}

function repaintFolderTree() {
	$("#selFolders option").each(function() {
		if(folderTreeNodeDropable($(this).val().split("_")[0])) {
			$(this).css("color", "black");
		} else {
			$(this).css("color", "gray");
		}
	});
	if(folderTreeNodeDropable($("#selFolders").val().split("_")[0])) {
		$("#selFolders").css("color", "black");
	} else {
		$("#selFolders").css("color", "gray");
	}
}

function updateContents(saveAsDraft) {
	if(!g_hasFocus) {
		alert("Die Seite kann nicht gespeichert werden, da diese nicht im Navigationsbaum ausgewählt ist.");
		return;
	}

	var ttl = $("#txtTitle").val();
	var als = $("#txtAlias").val();
	var url = $("#txtUrl").val();
	var ift = $("#txtInfotext").val();
	var tti = $("#txtIcon").val();
	var ttia = $("#txtIconAlt").val();
	var ttit = $("#txtIconTitle").val();
	var ttdis = $("#txtTitleDisplay").val();

	// if icon presented, then title can be empty, but not icon alt-text or title-text
	if(tti != "") {
		if(ttia == "" || ttit == "") {
			alert("Icon-Alttext / Titeltext darf nicht leer sein!");
			return;
		}

		// if title is empty, then alias must be provided!
		if((ttl == "") && (als == "")) {
			als = ttit;
		}
	} else {
		if(ttl == "" || ttl == undefined) {
			alert("Die Seite muss einen Titel haben, damit sie gespeichert werden kann.");
			return;
		}
	}

	if(url != "") {
		if(!confirm("Sie haben eine URL eingegeben, daher wird der Inhalt nicht gespeichert. Sind Sie sicher?")) {
			return;
		}
	}

	var crnode = navTreeOper.getCurrentNode();
	if(crnode == null) {
		alert("ERROR: Current-Node is null!");
		return;
	}

	g_newKey = crnode.key;

	var cnt = tinyMCE.get("txtContent").getContent();
	cnt = cnt.replace(new RegExp("<!-" + "-#", "g"), "<comment_ssi>");
	cnt = cnt.replace(/<!--/g, "<comment>");
	cnt = cnt.replace(/-->/g, "</comment>");

	var oldPath = navTreeOper.getPathInfo(crnode); // old path
	crnode.title = ttl;
	crnode.alias = als == undefined ? "" : filter(als);
	crnode.url = url == undefined ? "" : url;
	crnode.info_text = ift == undefined ? "" : ift;
	crnode.title_icon = tti == undefined ? "" : tti;
	crnode.title_icon_alt = ttia == undefined ? "" : ttia;
	crnode.title_icon_title = ttit == undefined ? "" : ttit;
	crnode.title_display = ttdis == undefined ? "" : ttdis;
	crnode.path = navTreeOper.getPathInfo(crnode); // with new filename!


	var saveDraft = "nein";
	if(saveAsDraft) {
		saveDraft = "ja";
	}

	$.post("app/update_contents.php", {
		"json_oper": "set_contents",
		"json_data": JSON.stringify(crnode),
		"content_html": cnt,
		"old_path": oldPath,
		"save_draft": saveDraft
	}, updateContentsCallback);
}

function loadHeads() {
	if(!g_headsLoaded) { // first load heads
		$.getJSON("app/edit_design.php?r=" + Math.random(), {
			"oper": "get_file_list"
		}, function(data) {
			var curr = data.current_design;
			var optHtml = "<option value=\"head.shtml\">head.shtml</option>";
			for(var i = 0; i < data.designs.length; i++) {
				if(data.designs[i].value == curr) {
					optHtml += "<option value=\"" + data.designs[i].value + "\" selected=\"selected\">" + data.designs[i].text + "</option>";
				} else {
					optHtml += "<option value=\"" + data.designs[i].value + "\">" + data.designs[i].text + "</option>";
				}
			}
			$("#selCustomHead").empty().append(optHtml);
		});
		g_headsLoaded = true;
	}
}

function keepSession() {
	$.post("app/keep_session.php");
}

$(document).ready(function() {
	/* TreeObj init... */
	navTreeOper.debugInfoPanelId = "debug";
	navTreeOper.dirDivIdNormal = "pnlNormal";
	navTreeOper.dirDivIdExtra = "pnlExtra";
	navTreeOper.dirDivIdSpec = "pnlSpec";
	navTreeOper.dirHtmlPanelId = "dirTreePanel";
	navTreeOper.nodeEventFuncName = "registerDirNodeEvents();"; // reg. events
	navTreeOper.init();

	resetTextBox();

	// display ajax error info.
	$("#debug").ajaxError(function(event, request, settings) {
		$(this).html("<span style='color:red;'>Error requesting page " + settings.url + "</span>");
	});

	$("#btnPublishTree").click(function() {
		if(confirm("Sind Sie sicher, dass Sie die Seite veröffentlichen wollen?")) {
			publishTree();
		}
	});

	$("#btnAddSibling").click(function() {
		if(getCurrentPermission() == "No" || getCurrentPermission() == "Child") {
			alert("Permission denied!");
			return;
		}

		// refresh Tree-Data
		btnStartClickEx(function() {
			var newSiblingTitle = prompt("Titel?", "Neuer Titel");
			if(newSiblingTitle) {
				var snode = navTreeOper.addSibling(newSiblingTitle);
				if(snode == null) return;
				snode.path = navTreeOper.getPathInfo(snode);
				g_newKey = snode.key;
				var templname = $("#selTempl").val();
				$.post("app/update_contents.php", {
					"json_oper": "create_sibling",
					"json_data": JSON.stringify(snode)
				}, addNodeCallback);
			}
		});

	});

	$("#btnNewExtraNode").click(function() {
		var newExtraNodeTitle = prompt("Titel?", "Neuer Titel");
		if(newExtraNodeTitle) {
			navTreeOper.setCurrentKey("Z0");
			navTreeOper.setCurrentNodeGroup("Z");
			var snode = navTreeOper.addSibling(newExtraNodeTitle);
			if(snode == null) return;
			g_newKey = snode.key;
			snode.path = navTreeOper.getPathInfo(snode);
			var templname = $("#selTempl").val();
			$.post("app/update_contents.php", {
				"json_oper": "create_sibling",
				"json_data": JSON.stringify(snode)
			}, addNodeCallback);
		}
	});

	$("#btnNewSpecNode").click(function() {
		var newSpecNodeTitle = prompt("Titel?", "Neuer Titel");
		if(newSpecNodeTitle) {
			navTreeOper.setCurrentKey("S0");
			navTreeOper.setCurrentNodeGroup("S");
			var snode = navTreeOper.addSibling(newSpecNodeTitle);
			if(snode == null) return;
			g_newKey = snode.key;
			snode.path = navTreeOper.getPathInfo(snode);
			var templname = $("#selTempl").val();
			$.post("app/update_contents.php", {
				"json_oper": "create_sibling",
				"json_data": JSON.stringify(snode)
			}, addNodeCallback);
		}
	});

	$("#btnAddChild").click(function() {
		if(getCurrentPermission() == "No") {
			alert("Permission denied!");
			return;
		}

		// refresh Tree-Data
		btnStartClickEx(function() {
			var newChildTitle = prompt("Titel?", "Neuer Titel");
			if(newChildTitle) {
				var onode = navTreeOper.getCurrentNode();
				var opath = navTreeOper.getPathInfo(onode);
				var cnode = navTreeOper.addChild(newChildTitle);
				if(cnode == null) return;
				g_newKey = cnode.key;
				cnode.path = navTreeOper.getPathInfo(cnode);
				var templname = $("#selTempl").val();
				$.post("app/update_contents.php", {
					"json_oper": "create_child",
					"json_data": JSON.stringify(cnode),
					"parent_node_path": opath
				}, addNodeCallback);
			}
		});

	});

	$("#btnRemove").click(function() {
		if(getCurrentPermission() == "No") {
			alert("Permission denied!");
			return;
		}

		// refresh Tree-Data
		btnStartClickEx(function() {
			if(confirm("Sind Sie sicher, dass diese Seite entfernt werden soll?")) {
				var cnode = navTreeOper.getCurrentNode();
				navTreeOper.addPathInfo(cnode);
				navTreeOper.remove();
				$.post("app/update_contents.php", {
					"json_oper": "remove",
					"json_data": JSON.stringify(cnode)
				}, removeCallback);
			}
		});

	});

	$("#btnCut").click(function() {
/*		if(confirm("Are you sure?")) {
			navTreeOper.cut();
		}*/
		alert("INFO: This function is not implemented yet, sorry.");
	});

	$("#btnPaste0").click(function() {
/*		navTreeOper.pasteAsSibling();*/
		alert("INFO: This function is not implemented yet, sorry.");
	});

	$("#btnPaste1").click(function() {
/*		navTreeOper.pasteAsChild();*/
		alert("INFO: This function is not implemented yet, sorry.");
	});

	$("#btnSaveDraft").click(function() {
		if(getCurrentPermission() == "No") {
			alert("Permission denied!");
			return;
		}
		// refresh Tree-Data
		btnStartClickEx(function() {
			updateContents(true);
		});

	});

	$("#btnUpdate").click(function() {
		if(getCurrentPermission() == "No") {
			alert("Sie haben nicht die notwendigen Rechte um die Seite zu publizieren.");
			return;
		}
		if(confirm("Möchten Sie die Seite wirklich publizieren?")) {
			// refresh Tree-Data
			btnStartClickEx(function() {
				updateContents(false);
			});

		}
	});

	$("#btnRecovery").click(function() {
		doRecovery();
	});

	$("#btnMoveUp").click(function() {
		btnStartClickEx(function() {
			var mKey = navTreeOper.moveUp();
			resetTextBox();

			drawPermission();
			foldNavTree();
			btnSaveTreeClick();
			treeNodeClick(mKey);
		});
	});

	$("#btnMoveDown").click(function() {
		btnStartClickEx(function() {
			var mKey = navTreeOper.moveDown();
			resetTextBox();

			drawPermission();
			foldNavTree();
			btnSaveTreeClick();
			treeNodeClick(mKey);
		});
	});

	$("#btnMoveToFolder").click(function() {
		if($($("#selFolders option:selected")[0]).css("color") == "gray") {
			alert("Der Zugriff auf das Verzeichnis wurde verweigert.");
			return;
		}
		var targetInfo = $("#selFolders").val().split("_");
		var targetNodeKey = targetInfo[0];
		var targetPath = targetInfo[1];
		var sourceNodeKey = navTreeOper.getCurrentNode().key;
//		alert("Source: " + sourceNodeKey + ", Target: " + targetNodeKey);

		btnStartClickEx(function() {
//			alert("CurrentNodeKey: " + navTreeOper.getCurrentNode().key);
			navTreeOper.copy();
			navTreeOper.setCurrentNode(navTreeOper.locateNode(targetNodeKey));
			if(targetNodeKey == "U") {
				navTreeOper.pasteAsSibling();
			} else {
				navTreeOper.pasteAsChild();
			}
			navTreeOper.setCurrentNode(navTreeOper.locateNode(sourceNodeKey));
			navTreeOper.remove();

			$.post("app/update_contents.php", {
				"json_oper": "move",
				"json_data": JSON.stringify(currentSelectedNode),
				"src_path": currentSelectedNode.path,
				"dst_path": targetPath
			}, function(rdata) {
				alert(rdata);
				resetTextBox();
				drawPermission();
			});
			btnSaveTreeClick();
		});
	});

	$("#lnkToggleOptions").click(function() {
		$("#divPageOptions").toggle();
	});

	$("#btnReloadTree").click(function() {
//		btnStartClick();
		btnStartClickEx();
	});

	$("#btnNavIdxRecover").click(function() {
		if(confirm("Sind Sie sicher?")) {
			var navTreeBackupFile = $("#selNavIdxBackupList").val();
			if(navTreeBackupFile != "0") {
				$.post("app/update_contents.php", {
					"json_oper": "recover_navindex",
					"json_data": "",
					"navindex_backup_file_name": navTreeBackupFile
				}, function(rdata) {
					alert(rdata);
					btnStartClick();
				});
			}
		}
	});

	$("#btnFixZeichen").click(function() {
		if(confirm("Sind Sie sicher?")) {
			$.post("app/update_contents.php", {
				"json_oper": "temp_recover_navtree_wrong_code",
				"json_data": ""
			}, function(rdata) {
				alert(rdata);
				location.reload();
			});
		}
	});

	$("#btnCustomHead").click(function() {
		var customHead = $("#selCustomHead").val();
		var curFile = navTreeOper.getPathInfo(navTreeOper.getCurrentNode());
		$.post("app/update_contents.php", {
			"json_oper": "change_ssi_head",
			"file_path": curFile,
			"new_head": customHead,
			"json_data": ""
		}, function(rdata) {
			alert(rdata);
		});
	});

	// help
	$("#helpHand a").click(function() {
		if(helpText == "") {
			$.get("app/get_help.php?r=" + Math.random(), {
				"page_name": "nav_editor"
			}, function(rdata){
				helpText = rdata;
				$("#helpCont").html(helpText);
				$("#helpCont").slideToggle("fast");
			});
		} else {
			$("#helpCont").slideToggle("fast");
		}
	});

	loadHeads();

	makeTreeDirty(g_treeIsDirty);

	setInterval("keepSession()", 1000 * <?php echo($ne2_config_info['js_keep_session_time']); ?>);
});



function trythis(){
	treeNodeClickEx('/test2/667.shtml');
	treeNodeClick(navTreeOper.getCurrentNode().key);
	foldNavTree();
}
//trythis();

// -->
</script>
</head>

<body id="bd_Nav" onload="btnStartClick();">
<div id="wrapper">
	<h1 id="header"><?php echo($ne2_config_info['app_title']); ?></h1>
	<div id="navBar">
		<?php require('common_nav_menu.php'); ?>
	</div>

	<div id="dirTreePanel">
		<fieldset>
			<legend>Navigationsbaum bearbeiten</legend>
			<input type="button" id="btnPublishTree" class="button" value="Publizieren" disabled="true" style="font-weight:bold;" />
			<input type="button" id="btnReloadTree" value="Refresh" title="Navigationsbaum neu einlesen" class="button" />


			<div style="margin:0 0 0.25em 0;"></div>

			<input type="button" id="btnAddSibling" value="Seite erstellen" class="button" title="Neue Seite erstellen" />
			<input type="button" id="btnAddChild" value="Unterseite erstellen" class="button" title="Neue Unterseite erstellen" />
			<input type="button" id="btnRemove" value="L&ouml;schen" class="button" title="Gew&auml;hlte Seite l&ouml;schen" />
			<br />
			<input type="button" id="btnMoveUp" value="&uarr;" title="Nach oben verschieben" class="button" />
			<input type="button" id="btnMoveDown" value="&darr;" title="Nach unten verschieben" class="button" />


		</fieldset>

		<h4>Hauptmen&uuml;</h4>
		<div id="pnlNormal"></div>

		<h4>Optionales Zielgruppenmenü <a href="javascript:;" id="btnNewExtraNode" style="display:none;">[Neu...]</a></h4>
		<div id="pnlExtra"></div>

		<h4>Technisches Men&uuml; <a href="javascript:;" id="btnNewSpecNode" style="display:none;">[Neu...]</a></h4>
		<div id="pnlSpec"></div>

		<h4>Wiederherstellen</h4>
		<div>
			<select id="selNavIdxBackupList" class="textBox">
				<option value="0" selected="selected">-- Backup-Liste --</option>
			</select>
			<input type="button" id="btnNavIdxRecover" class="button" value="Wiederherstellen" />
		</div>

		<div style="display:none;">
			<br />
			<input type="button" id="btnFixZeichen" value="Zeichen fix" class="button" />
			<a href="javascript:;" title="Korrigiert Unicode-Zeichen (&quot;u00...&quot;) der &auml;lteren Versionen. Bitte nur dann benutzen, wenn Sie diese Fehler in der Navigation haben: z.B.: \u00fc statt &quot;&uuml;&quot;, \u00f6 statt &quot&ouml;&quot; usw.">[?]</a>
		</div>
	</div>
	<div id="contentPanel">
	<?php
	if(has_help_file()) {
	?>
		<div id="helpCont"> .</div>
		<div id="helpHand"><a href="javascript:;">Hilfe</a></div>
	<?php
	}
	?>

		<form action="" method="post" name="frmEdit" id="frmEdit">
			<fieldset>
				<legend>Seiten bearbeiten</legend>
				<p>
					<label for="txtTitle">Seitentitel:</label>
					<input type="text" id="txtTitle" class="textBox" name="txtTitle" size="48" />
                                </p>
                                <div id="ne-editor-optionbox">
                                <p>
					<a id="lnkToggleOptions" href="javascript:;">Optionen</a> |
					<a id="lnkPreview" href="javascript:;">Seite aufrufen...</a>

				</p>
                                 <div id="divPageOptions" style="display:none;">
                                            <p>
                                                    <label for="txtTitleDisplay">Titel mit HTML:</label>
                                                    <input type="text" id="txtTitleDisplay" name="txtTitleDisplay" size="48" class="textBox" style="width:50%;" />
                                            </p>
                                            <p>
                                                    <label for="txtAlias">Alias (Dateiname ohne Endung):</label>
                                                    <input type="text" id="txtAlias" name="txtAlias" size="16" class="textBox" style="width:12em;" title="Ein Aliasname f&uuml;r diese Seite, gilt auch als Filename" />
                                              </p>
                                            <p>
                                                    <label for="txtInfotext">Infotext:</label>
                                                    <input type="text" id="txtInfotext" name="txtInfotext" size="16" class="textBox" style="width:50%;" title="Kommentar der Seite" />
                                            </p>
                                            <p>
                                                    <label for="txtUrl">URL:</label>
                                                    <input type="text" id="txtUrl" name="txtUrl" size="32" class="textBox" style="width:50%;" title="Wenn vorhanden, wird ein Link gezeigt, kein Inhalt n&ouml;tig." />
                                                    <br style="clear: left;" />
                                                    <label>&nbsp;</label><span style="font-size:90%;color:#666;">Achtung: Wenn vorhanden, wird ein Link gezeigt; Inhalte werden ignoriert.</span>
                                            </p>
                                            <p class="reihe">
                                                    <label for="txtIcon">Icon:</label>
                                                    <input type="text" id="txtIcon" name="txtIcon" size="32" class="textBox" style="width:180px;" />
                                                    <label for="txtIconAlt">Icon-Alttext:</label>
                                                    <input type="text" id="txtIconAlt" name="txtIconAlt" size="32" class="textBox" style="width:180px;" />
                                                    <label for="txtIconTitle">Icon-Titel:</label>
                                                    <input type="text" id="txtIconTitle" name="txtIconTitle" size="32" class="textBox" style="width:180px;" />
                                            </p>

                                        <!--
                                            <p>
                                                    <label>Design wechseln:</label>
                                                    <select id="selCustomHead" class="textBox">
                                                            <option selected="selected" value="-1">laden...</option>
                                                    </select>
                                                    <input type="button" id="btnCustomHead" class="button" value="wechseln" />
                                            </p>
                                        -->
                                    </div>
                                </div>
				<div id="ne-editor-content">
                                    <textarea id="txtContent" class="ne-editor-content-textarea" name="txtContent" rows="32" style="width: 100%"></textarea>
				</div>

				<div style="margin:0.25em 0 0 0;">
					<input type="button" id="btnSaveDraft" name="btnSaveDraft" value="Entwurf speichern" class="button" title="Seite wird zwischengespeichert" style="padding:0.25em 1em;" />
					<input type="button" id="btnUpdate" name="btnUpdate" value="Publizieren" class="button" title="Seite wird publiziert" style="padding:0.25em 1em;" />
					<span id="lockPrompt"></span>
					<span id="draftPrompt"></span>
				</div>
			</fieldset>
			<fieldset>
				<legend>Wiederherstellung der Dateien</legend>
                                <div id="formRecovery">
                                    <select id="selOldVersions" class="textBox">
                                        <option value="0" selected="selected">-- Backup-Liste --</option>
                                     </select>
                                     <input type="button" id="btnRecovery" name="btnRecovery" class="button" value="Wiederherstellen" />
                                </div>
			</fieldset>

			<fieldset>
				<legend>Seite verschieben</legend>
				<label>Zielverzeichnis:</label>
				<select id="selFolders" class="textBox"></select>
				<input type="button" id="btnMoveToFolder" class="button" value="Verschieben" />
			</fieldset>

		</form>
		<div id="debug">:-)</div>
	</div>
<?php require('common_footer.php'); ?>
</div>
</body>

</html>
