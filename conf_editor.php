<?php
require_once('auth.php');
require_once('app/config.php');

// help
function has_help_file() {
	global $ne2_config_info;
	$help_file = $ne2_config_info['help_path'] .'conf_editor'. $ne2_config_info['help_filesuffix'] ;
	return file_exists($help_file);
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Konfigurationen bearbeiten - <?php echo($ne2_config_info['app_titleplain']); ?></title>
<link rel="stylesheet" type="text/css" href="css/styles.css?<?php echo date('Ymdis'); ?>" />

<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="js/json2.js"></script>
<script type="text/javascript">
<!--
var loadFeedImportDone = false;
var feedCounter = 0;

var loadWebsiteConfDone = false;

var loadVorlagenDone = false;

var loadConfDone = false;

var loadConfListDone = false;

var currentConfFileName = "";

var helpText = "";

function loadConfList() {
	$.getJSON("app/edit_conf.php?r=" + Math.random(), {
		"oper": "get_conf_list",
		"conf_file_name": ""
	}, loadConfListCallback);
}

function loadConfListCallback(rdata) {
	// TODO
	cflHtml = "";
	for(var i = 0; i < rdata.length; i++) {
		cflHtml += "<li><a href='javascript:;' rel='" + rdata[i].file_name + "'>" + rdata[i].file_name + "</a></li>";
	}
	// ne2_config is special
	cflHtml += "<li><a href='javascript:;' rel='ne2_config.conf'>ne2_config.conf</a></li>";
	$("#confList ul").html(cflHtml);
	loadConfListDone = true;

	$("#confList a").click(function() {
		var cfn = $(this).attr("rel");
		switch(cfn) {
			case "feedimport.conf":
				showPanel("fld_feedimport");
				loadFeedImport();
				break;
//			case "vorlagen.conf":
//				showPanel("fld_vorlagen");
//				loadVorlagen();
//				break;
			case ".htusers":
				showPanel("fld_htusers");
				loadHtUsers();
				break;
			case "hthosts":
				showPanel("fld_hthosts");
				loadHtHosts();
				break;
			case "<?php echo($ne2_config_info['website']); ?>":
				showPanel("fld_website");
				$("#fld_common legend").text(cfn);
				loadWebsiteConf();
				break;
			default:
				showPanel("fld_common");
				$("#fld_common legend").text(cfn);
				currentConfFileName = cfn;
				loadConf(cfn);
				break;
		}
	});

	// init. panel
	showPanel("fld_feedimport");
	loadFeedImport();
}

/* load .htusers */
function loadHtUsers() {
	$("#htusers").html("Loading...");
	$.getJSON("app/edit_conf.php?r=" + Math.random(), {
		"oper": "get_htusers",
		"conf_file_name": ""
	}, function(rdata) {
		var huHtml = "";
		for(var i = 0; i < rdata.length; i++) {
			huHtml += "<p>" + rdata[i].username;
			huHtml += " <a rel='" + rdata[i].username + "' href='javascript:;'>[X]</a></p>";
		}
		$("#htusers").html(huHtml);
		// delete htuser event
		$("#htusers p a").click(function() {
			if(confirm("Sind Sie sicher?")) {
				var un = $(this).attr("rel");
				$.post("app/edit_conf.php", {
					"oper": "delete_htuser",
					"conf_file_name": "",
					"username": un
				}, function(msg){
					alert(msg);
					loadHtUsers();
				});
			}
		});
	});
}

// load hthosts
function loadHtHosts() {
	$("#hthosts").html("Loading...");
	$.getJSON("app/edit_conf.php?r=" + Math.random(), {
		"oper": "get_hthosts",
		"conf_file_name": ""
	}, function(rdata) {
		var hhHtml = "";
		for(var j = 0; j < rdata.length; j++) {
			hhHtml += "<p>" + rdata[j].host;
			hhHtml += " <a rel='" + rdata[j].host + "' href='javascript:;'>[X]</a></p>";
		}
		$("#hthosts").html(hhHtml);
		// delete hthost event
		$("#hthosts p a").click(function() {
			if(confirm("Sind Sie sicher?")) {
				var ht = $(this).attr("rel");
				$.post("app/edit_conf.php", {
					"oper": "delete_hthost",
					"conf_file_name": "",
					"host": ht
				}, function(msg) {
					alert(msg);
					loadHtHosts();
				});
			}
		});
	});
}

/* load normal conf-file */
function loadConf(confFileName) {
	$("#container").html("Loading...");
	$.getJSON("app/edit_conf.php?r=" + Math.random(), {
		"oper": "get_conf",
		"conf_file_name": confFileName
	}, loadConfCallback);
}

function loadConfCallback(rdata) {
	var ciHtml = "";
	for(var i = 0; i < rdata.length; i++) {
		var ciName = rdata[i].opt_name;
		var ciNameLabel = "<p><label>" + ciName + "</label> ";
		var ciValue = "<input type='text' class='textBox' style='width:24em;' value='" + rdata[i].opt_value + "' /> ";
		ciHtml += (ciNameLabel + ciValue);
	}
	$("#container").html(ciHtml);
	loadConfDone = true;
}

/*website.conf related */
function loadWebsiteConf() {
	var confFileName = 'website.conf';
	$("#websiteContainer").html("Loading...");
	$.getJSON("app/edit_conf.php?r=" + Math.random(), {
		"oper": "get_conf",
		"conf_file_name": confFileName
	}, loadWebsiteConfCallback);
}

function loadWebsiteConfCallback(rdata) {
	var ciHtml = "";
	for(var i = 0; i < rdata.length; i++) {
		var ciName = rdata[i].opt_name;
		var ciNameLabel = "<p><label type='varName'>" + ciName + "</label> ";
		var ciValue = "<input type='text' class='textBox' style='width:24em;' id='" + ciName + "' value='" + rdata[i].opt_value + "' /> ";
		ciHtml += (ciNameLabel + ciValue);
		if (ciName == 'name-des-Webauftritts') {
		ciHtml += "<input type='checkbox' id='chkAllowHtml' name='chkAllowHtml' value='ja' /><label for='chkAllowHtml'>Eigene HTML-Anweisungen zulassen</label>";
		}
	}
	$("#websiteContainer").html(ciHtml);
	loadWebsiteConfDone = true;
}

function saveContentCallback(data) {
	$("#ajaxLoader").hide();
	//alert(data);
	saveVars(data);
}
//for every button save vars
function saveVars(data){
	var poData = [];
	$("#websiteContainer p").each(function() {
		var ccName = $(this).find("label[type=varName]").text();
		var ccValue = $(this).find("input[type=text]").val();
		var ccItem = {
			"opt_name": ccName,
			"opt_value": ccValue
		};
		poData.push(ccItem);
	});
	$("#genWebItemsContainer p").each(function() {
		var gcName = $(this).find("input[name=txtGenItemCf_Name]").val();
		var gcValue = $(this).find("input[name=txtGenItemCf_Val]").val();
		var gcItem = {
			"opt_name": gcName,
			"opt_value": gcValue
		};
		poData.push(gcItem);
	});
		$.post("app/edit_conf.php", {
			"oper": "set_conf",
			"conf_file_name": 'website.conf',
			"jsonData": JSON.stringify(poData)
		}, function(rdata) {
			alert(data);
			$("#genWebItemsContainer").html("<div class='mi'></div>");
			loadWebsiteConf();
		});
}


/* vorlagen.conf related */
function loadVorlagen() {
	// vorlagen
	$.getJSON("app/edit_conf.php?r=" + Math.random(), {
		"oper": "get_vorlagen",
		"conf_file_name": "vorlagen.conf"
	}, loadVorlagenCallback); // load tree data
}

function loadVorlagenCallback(data) {
	var chkHtml = "";
	for(var i = 0; i < data.length; i++) {
		chkHtml += "<label for=\"chkvlgc" + i.toString() + "\">" + data[i].item + "</label> ";
		chkHtml += "<input type=\"checkbox\" id=\"chkvlgc" + i.toString() + "\"" + (data[i].value == 1 ? " checked=\"checked\"" : "") + " value=\"" + data[i].item + "\" />";
		chkHtml += "<br />";
	}
	$("#vorlagen").html(chkHtml);
	loadVorlagenDone = true;
}

function saveVorlagenCallback(data) {
	alert(data);
	location.reload();
}

function buildJSONVorlagen() {
	var arrJSON = [];
	var chks = $("#vorlagen :checkbox");
	for(var i = 0; i < chks.length; i++) {
		arrJSON.push(
			{
				"item": chks[i].value,
				"value": chks[i].checked == true ? 1 : 0
			}
		);
	}
	return JSON.stringify(arrJSON);
}

/* feedimport.conf related */
function loadFeedImport() {
	// feedimport
	$.getJSON("app/edit_conf.php?r=" + Math.random(), {
		"oper": "get_feedimport",
		"conf_file_name": "feedimport.conf"
	}, loadFeedImportCallback);
}

function loadFeedImportCallback(rdata) {
	feedHtml = "";
	for(var i = 0; i < rdata.feeds.length; i++) {
		var feedId = rdata.feeds[i].id;
		var feedIdLabel = "<p><label>Feed-" + feedId + "</label> ";
		var feedTitle = "<input type='text' id='txtFeedTitle_" + feedId + "' class='textBox' size='32' value='" + rdata.feeds[i].title + "' /> ";
		var feedUrl = "<input type='text' id='txtFeedUrl_" + feedId + "' class='textBox' size='48' value='" + rdata.feeds[i].url + "' /> [<a href='javascript:;'>X</a>]</p>";
		feedHtml += (feedIdLabel + feedTitle + feedUrl);
		feedCounter++;
	}
	$("#feedimport").html(feedHtml + "<div class='mi'></div>");

	feedOptHtml = "";
	for(var j = 0; j < rdata.options.length; j++) {
		var feedOptName = rdata.options[j].opt_name;
		var feedOptNameLabel = "<p><label>" + feedOptName + "</label> ";
		var feedOptValue = "<input type='text' class='textBox' size='32' value='" + rdata.options[j].opt_value + "' /> ";
		feedOptHtml += (feedOptNameLabel + feedOptValue);
	}
	$("#feedimportOpts").html(feedOptHtml);

	// add event for removing
	$("#feedimport p a").click(function() {
		if(confirm("Sure to remove this item?")) {
			$(this).parent().remove();
		}
	});

	loadFeedImportDone = true;
}

function showPanel(panelId) {
	$("#contentPanel2 fieldset").hide(); // hide all
	$("#contentPanel2 #" + panelId).show();
}

/* ---------- Here comes jQuery: ---------- */
$(document).ready(function() {
	/* website.conf buttons functions begin */
	$("#btnUpdate").click(function() {
		if(!loadWebsiteConfDone) {
			return;
		}
		var text = $("#name-des-Webauftritts").val();
		var desc = $("#kurzbeschreibung-zum-Webauftritt").val();
		var imgUrl = $("#logo-URL").val();
		var imgAlt = $("#logo-Alt").val();
		var siteTitle = $("#titel-des-Webauftritts").val();
		var imgH = $("#logo-Height").val();
		var imgW = $("#logo-Width").val();

		// if image specified, then alt-text cannot be empty!
		if(imgUrl != "") {
			if(imgAlt == "") {
				alert("Bitte geben Sie die Beschreibung zu Ihrem Logobild an!");
				return false;
			}
		}

		if(confirm("Are you sure to save?")) {
			if(imgUrl != "") {
				img = "<img alt=\"" + imgAlt + "\" src=\"" + imgUrl + "\" width=\"" + imgW + "\" height=\"" + imgH + "\" border=\"0\" />";
			}
			btnNameTmp = $(this).val();
			$(this).val("Moment...");
			$(this).attr("disabled", "disabled");
			var templname = $("#selTempl").val();
			var pdata = {
				"content_text": text,
				"content_desc": desc,
				"content_img": img,
				"content_img_alt": imgAlt,
				"site_title_text": siteTitle,
				"content_allow_html": $("#chkAllowHtml:checked").length > 0 ? true : false
			};
			$.post("app/edit_logo.php", {
				"json_oper": "update_content",
				"json_content": JSON.stringify(pdata),
				"template_name": templname
			}, function(data){
			$("#btnUpdate").val(btnNameTmp);
			$("#btnUpdate").removeAttr("disabled");
			saveContentCallback(data);
			});
		}
	});

	$("#btnUpdateExisted").click(function() {
		if(!loadWebsiteConfDone) {
			return;
		}
		if(confirm("Are you sure to replace the Logos of all the existing pages?")) {
			$("#ajaxLoader").show();
			btnNameTmp = $(this).val();
			$(this).val("Moment...");
			$(this).attr("disabled", "disabled");
			var text = $("#name-des-Webauftritts").val();
			var desc = $("#kurzbeschreibung-zum-Webauftritt").val();
			var imgUrl = $("#logo-URL").val();
			var imgAlt = $("#logo-Alt").val();
			var siteTitle = $("#titel-des-Webauftritts").val();
			var imgH = $("#logo-Height").val();
			var imgW = $("#logo-Width").val();

			if(imgUrl != "") {
				img = "<img alt=\"" + imgAlt + "\" src=\"" + imgUrl + "\" width=\"" + imgW + "\" height=\"" + imgH + "\" border=\"0\" />";
			}
			var templname = $("#selTempl").val();
			var pdata = {
				"content_text": text,
				"content_desc": desc,
				"content_img": img,
				"content_img_alt": imgAlt,
				"site_title_text": siteTitle,
				"content_allow_html": $("#chkAllowHtml:checked").length > 0 ? true : false
			};
			$.post("app/edit_logo.php", {
				"json_oper": "update_content_all",
				"json_content": JSON.stringify(pdata),
				"template_name": templname
			}, function(data){
			$("#btnUpdateExisted").val(btnNameTmp);
			$("#btnUpdateExisted").removeAttr("disabled");
			saveContentCallback(data);
			});
		}
	});



	$("#save_osm").click(function() {
		if(!loadWebsiteConfDone) {
			return;
		}
		btnNameTmp = $(this).val();
		$(this).val("Moment...");
		$(this).attr("disabled", "disabled");
		var inst = $("#name").attr("value");
		var street = $("#strasse").attr("value");
		var plz = $("#plz").attr("value");
		var city = $("#ort").attr("value");
		var personname = $("#kontakt1-name").attr("value");
		var personvorname = $("#kontakt1-vorname").attr("value");
		var telefon = $("#telefon").attr("value");
		var fax = $("#fax").attr("value");
		var email = $("#email").attr("value");
		var lat = $("#geo-lat").attr("value");
		var lon = $("#geo-long").attr("value");

		$.post("app/save_osm.php", { inst: inst, street: street, plz: plz, city: city, personname: personname, personvorname: personvorname, telefon: telefon, fax: fax, email: email, lat: lat, lon: lon}, function(resp) {
			//alert("kontakt.shtml wurde erstellt");
			$("#save_osm").val(btnNameTmp);
			$("#save_osm").removeAttr("disabled");
			saveVars("kontakt.shtml wurde erstellt");
			$('#hr').append('<p>Done.</p>');
		});
	});

	$("#btnWebAddConf").click(function() {
		var genItemHtml = "";
		var newItemHtml = "<p><input type='text' name='txtGenItemCf_Name' class='textBox' style='width:19.75em;' />";
		newItemHtml += " <input type='text' name='txtGenItemCf_Val' class='textBox' style='width:24em;' /> [<a href='javascript:;'>X</a>]</p>";
		$("#genWebItemsContainer .mi").replaceWith(genItemHtml + newItemHtml + "<div class='mi'></div>");

		// for removing
		$("#genWebItemsContainer p a").click(function() {
			if(confirm("Sure to remove?")) {
				$(this).parent().remove();
			}
		});
	});

	$("#btnWebUpdConf").click(function() {
		if(!loadWebsiteConfDone) {
			return;
		}
		var poData = [];
		$("#websiteContainer p").each(function() {
			var ccName = $(this).find("label[type=varName]").text();
			var ccValue = $(this).find("input[type=text]").val();
			var ccItem = {
				"opt_name": ccName,
				"opt_value": ccValue
			};
			poData.push(ccItem);
		});
		$("#genWebItemsContainer p").each(function() {
			var gcName = $(this).find("input[name=txtGenItemCf_Name]").val();
			var gcValue = $(this).find("input[name=txtGenItemCf_Val]").val();
			var gcItem = {
				"opt_name": gcName,
				"opt_value": gcValue
			};
			poData.push(gcItem);
		});
//		alert(JSON.stringify(poData));
		if(confirm("Are you sure to update?")) {
			$(this).val("Moment...");
			$(this).attr("disabled", "disabled");
			$.post("app/edit_conf.php", {
				"oper": "set_conf",
				"conf_file_name": 'website.conf',
				"jsonData": JSON.stringify(poData)
			}, function(rdata) {
				alert(rdata);
				$("#btnWebUpdConf").val("Nur Variablen speichern");
				$("#btnWebUpdConf").removeAttr("disabled");
				$("#genWebItemsContainer").html("<div class='mi'></div>");
				loadWebsiteConf();
			});
		}
	});
	/* website.conf buttons functions end */



	/* Vorlagen related */
	$("#btnUpdVorlagen").click(function() {
		if(!loadVorlagenDone) {
			return;
		}

		if(confirm("Are you sure to save?")) {
			$.post("app/edit_conf.php", {
				"oper": "set_vorlagen",
				"json_data": buildJSONVorlagen(),
				"conf_file_name": "vorlagen.conf"
			}, saveVorlagenCallback);
		}
	});
	/* /Vorlagen related */

	/* Feed-Import related */
	$("#btnAddFeedBox").click(function() {
		if(loadFeedImportDone) {
			feedCounter++;
			var newFeedBoxHtml = "<p><label>Feed-" + feedCounter + "</label> ";
			newFeedBoxHtml += "<input type='text' id='txtFeedTitle_" + feedCounter + "' class='textBox' size='32' /> ";
			newFeedBoxHtml += "<input type='text' id='txtFeedUrl_" + feedCounter + "' class='textBox' size='48' /> [<a href='javascript:;'>X</a>]</p>";
			$("#feedimport .mi").replaceWith(newFeedBoxHtml + "<div class='mi'></div>");

			// add event for removing
			$("#feedimport p a").click(function() {
				if(confirm("Sure to remove this item?")) {
					$(this).parent().remove();
				}
			});
		}
	});

	$("#btnAddItemFeedImport").click(function() {
		var genItemHtml = "";
//		genItemHtml += $("#feedimportGenItems").html();
		var newItemHtml = "<p><input type='text' name='txtGenItemFI_Name' class='textBox' style='width:7.75em;' />";
		newItemHtml += " <input type='text' name='txtGenItemFI_Val' class='textBox' style='width:9.75em;' /> [<a href='javascript:;'>X</a>]</p>";
		$("#feedimportGenItems .mi").replaceWith(genItemHtml + newItemHtml + "<div class='mi'></div>");

		// for removing
		$("#feedimportGenItems p a").click(function() {
			if(confirm("Sure to remove?")) {
				$(this).parent().remove();
			}
		});
	});

	$("#btnUpdFeedImport").click(function() {
		if(!loadFeedImportDone) {
			return;
		}

		var postData = {
			"feeds": [],
			"options": [],
			"general_items": []
		};
		$("#feedimport p").each(function() {
			var feedIdStr = $(this).find("label").text();
			var feedId = feedIdStr.split("-")[1];
			var feedTitle = $(this).find("input").get(0).value;
			var feedUrl = $(this).find("input").get(1).value;
			var feedItem = {
				"id": feedId,
				"title": feedTitle,
				"url": feedUrl
			};
			postData.feeds.push(feedItem);
		});
		$("#feedimportOpts p").each(function() {
			var foName = $(this).find("label").text();
			var foValue = $(this).find("input").val();
			var foItem = {
				"opt_name": foName,
				"opt_value": foValue
			}
			postData.options.push(foItem);
		});
		$("#feedimportGenItems p").each(function() {
			var fgName = $(this).find("input[name=txtGenItemFI_Name]").val();
			var fgValue = $(this).find("input[name=txtGenItemFI_Val]").val();
			var fgItem = {
				"gi_name": fgName,
				"gi_value": fgValue
			};
			postData.general_items.push(fgItem);
		});
		if(confirm("Are you sure to update?")) {
			$(this).val("Moment...");
			$(this).attr("disabled", "disabled");
			$.post("app/edit_conf.php", {
				"oper": "set_feedimport",
				"conf_file_name": "feedimport.conf",
				"jsonData": JSON.stringify(postData)
			}, function(rdata) {
				alert(rdata);
				$("#btnUpdFeedImport").val("Update");
				$("#btnUpdFeedImport").removeAttr("disabled");
				location.reload();
			});
		}
	});

	/* common conf: post */
	$("#btnAddConf").click(function() {
		var genItemHtml = "";
		var newItemHtml = "<p><input type='text' name='txtGenItemCf_Name' class='textBox' style='width:19.75em;' />";
		newItemHtml += " <input type='text' name='txtGenItemCf_Val' class='textBox' style='width:24em;' /> [<a href='javascript:;'>X</a>]</p>";
		$("#genItemsContainer .mi").replaceWith(genItemHtml + newItemHtml + "<div class='mi'></div>");

		// for removing
		$("#genItemsContainer p a").click(function() {
			if(confirm("Sure to remove?")) {
				$(this).parent().remove();
			}
		});
	});

	$("#btnUpdConf").click(function() {
		if(!loadConfDone) {
			return;
		}
		var poData = [];
		$("#container p").each(function() {
			var ccName = $(this).find("label").text();
			var ccValue = $(this).find("input").val();
			var ccItem = {
				"opt_name": ccName,
				"opt_value": ccValue
			};
			poData.push(ccItem);
		});
		$("#genItemsContainer p").each(function() {
			var gcName = $(this).find("input[name=txtGenItemCf_Name]").val();
			var gcValue = $(this).find("input[name=txtGenItemCf_Val]").val();
			var gcItem = {
				"opt_name": gcName,
				"opt_value": gcValue
			};
			poData.push(gcItem);
		});
//		alert(JSON.stringify(poData));
		if(confirm("Are you sure to update?")) {
			$(this).val("Moment...");
			$(this).attr("disabled", "disabled");
			$.post("app/edit_conf.php", {
				"oper": "set_conf",
				"conf_file_name": currentConfFileName,
				"jsonData": JSON.stringify(poData)
			}, function(rdata) {
				alert(rdata);
				$("#btnUpdConf").val("Update");
				$("#btnUpdConf").removeAttr("disabled");
				$("#genItemsContainer").html("<div class='mi'></div>");
				loadConf(currentConfFileName);
			});
		}
	});

	// add new htuser
	$("#btnAddNewHtUser").click(function() {
		var un = $("#txtNewHtUserName").val();
		var pw = $("#txtNewHtUserPass").val();
		if(un != "" && pw != "") {
			$.post("app/edit_conf.php", {
				"oper": "add_htuser",
				"conf_file_name": "",
				"username": un,
				"password": pw
			}, function(msg) {
				alert(msg);
				loadHtUsers();
			});
			$("#txtNewHtUserName").val("");
			$("#txtNewHtUserPass").val("");
		}
	});

	// add new hthost
	$("#btnAddNewHtHost").click(function() {
		var host = $("#txtNewHtHost").val();
		if(host != "") {
			$.post("app/edit_conf.php", {
				"oper": "add_hthost",
				"conf_file_name": "",
				"host": host
			}, function(msg) {
				alert(msg);
				loadHtHosts();
			});
		}
	});

	// help
	$("#helpHand a").click(function() {
		if(helpText == "") {
			$.get("app/get_help.php?r=" + Math.random(), {
				"page_name": "conf_editor"
			}, function(rdata){
				helpText = rdata;
				$("#helpCont").html(helpText);
				$("#helpCont").slideToggle("fast");
			});
		} else {
			$("#helpCont").slideToggle("fast");
		}
	});

	loadConfList();

});
// -->
</script>
</head>

<body id="bd_Conf">
<div id="wrapper">
	<h1 id="header"><?php echo($ne2_config_info['app_title']); ?></h1>
	<div id="navBar">
		<?php require('common_nav_menu.php'); ?>
	</div>

	<div id="confList">
		<fieldset>
			<legend>Konfigurationsdatei</legend>
			<ul></ul>
		</fieldset>
	</div>

	<div id="contentPanel2">
	<?php
	// help
	if(has_help_file()) {
	?>
		<div id="helpCont">.</div>
		<div id="helpHand"><a href="javascript:;">Hilfe</a></div>
	<?php
	}
	?>
		<form id="frmEdit">
			<fieldset id="fld_feedimport">
				<legend>feedimport.conf</legend>
				<div id="feedimport">Loading...<div class="mi"></div></div>
				<hr size="1" noshade="noshade" />
				<div id="feedimportOpts"></div>
				<div id="feedimportGenItems"><div class="mi"></div></div>
				<hr size="1" noshade="noshade" />
				<input type="button" id="btnAddFeedBox" value="Feed einf&uuml;gen" class="button" />
				<input type="button" id="btnAddItemFeedImport" value="Konfig-Eintrag einf&uuml;gen" class="button" />
				<input type="button" id="btnUpdFeedImport" value="Update" class="button" />
			</fieldset>

			<fieldset id="fld_website">
				<legend></legend>
				<div id="websiteContainer">Loading...</div>
				<div id="genWebItemsContainer"><div class="mi"></div></div>
				<hr size="1" noshade="noshade" />
				<input type="button" id="btnWebAddConf" value="Eintrag einf&uuml;gen" class="button" />
				<input type="button" id="btnWebUpdConf" value="Nur Variablen speichern" class="button" />
				<br><br>
				<input type="button" id="btnUpdate" name="btnUpdate" value="Vorlagen Aktualisieren" class="button" />
				<input type="button" id="btnUpdateExisted" name="btnUpdateExisted" value="Existierte Seiten Aktualisieren" class="button" />
				<input type="hidden" id="selTempl" name="selTempl" value="seitenvorlage.html">
				<input id="save_osm" type="button" class="submit" name="submit" value="Kontaktseite erstellen" />
			</fieldset>

			<fieldset id="fld_common">
				<legend></legend>
				<div id="container">Loading...</div>
				<div id="genItemsContainer"><div class="mi"></div></div>
				<hr size="1" noshade="noshade" />
				<input type="button" id="btnAddConf" value="Eintrag einf&uuml;gen" class="button" />
				<input type="button" id="btnUpdConf" value="Update" class="button" />
			</fieldset>

			<fieldset id="fld_vorlagen">
				<legend>vorlagen.conf</legend>
				<div id="vorlagen">Loading...</div>
				<hr size="1" noshade="noshade" />
				<input type="button" id="btnUpdVorlagen" value="Update" class="button" />
			</fieldset>

			<fieldset id="fld_htusers">
				<legend>.htusers</legend>
				<div id="htusers"></div>
				<hr size="1" noshade="noshade" />
				<label for="txtNewHtUserName">Username</label>
				<input type="text" id="txtNewHtUserName" class="textBox" size="16" />
				<label for="txtNewHtUserPass">Password</label>
				<input type="password" id="txtNewHtUserPass" class="txtBox" size="16" />
				<input type="button" id="btnAddNewHtUser" value="Add" class="button" />
			</fieldset>

			<fieldset id="fld_hthosts">
				<legend>hthosts</legend>
				<div id="hthosts"></div>
				<hr size="1" noshade="noshade" />
				<label for="txtNewHtHost">Domain/IP</label>
				<input type="text" id="txtNewHtHost" class="textBox" size="16" />
				<input type="button" id="btnAddNewHtHost" value="Add" class="button" />
			</fieldset>
		</form>
	</div>

<?php require('common_footer.php'); ?>
</div>
</body>

</html>