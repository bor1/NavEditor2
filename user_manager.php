<?php
require_once('auth.php');
require_once('app/config.php');

// help
function has_help_file() {
	global $ne2_config_info;
	$help_file = $ne2_config_info['help_path'] .'user_manager'. $ne2_config_info['help_filesuffix'] ;
	return file_exists($help_file);
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<title>User management - <?php echo($ne2_config_info['app_titleplain']); ?></title>

<?php
    echo NavTools::includeHtml("default",
            "jquery-ui-1.8.18.custom.min.js",
            "jqueryui/ne2-theme/jquery-ui-1.8.17.custom.css",
            "json2.js",
            "naveditor2.js",
            "jquery.md5.js",
            "livevalidation_standalone.compressed.js",
            "jqueryFileTree.js",
            "jqueryFileTree.css",
            "live_validation.css"
            )
?>

<script type="text/javascript">
var admin_uname = "<?php echo(NavTools::getServerAdmin()); ?>";

var helpText = "";
var _user_data_array = [];
var _currentValues = [];
var _user_roles_array = $.parseJSON('<?php echo(json_encode($ne2_user_roles)); ?>') ;
var _user_modus_array = $.parseJSON('<?php echo(json_encode($ne2_user_modus)); ?>') ;
var _empty_user_data_array = $.parseJSON('<?php echo(json_encode(get_ne2_user_params_simple())); ?>');
var _not_editable_user_params_array = $.parseJSON('<?php echo(json_encode(get_ne2_user_params_not_editable())); ?>');
var _user_params_full = $.parseJSON('<?php echo(json_encode($ne2_user_params)); ?>');
var _somethingChanged = false;
var _check_boxes_status = [];

function load_validation(){
    //helper func.
    var setValidationPattern = function(field, pattern, errormsg){
        var validObj = new LiveValidation(""+field, {onValid: function(){}});
        validObj.add(Validate.Format, {
            pattern: pattern,
            failureMessage: errormsg
        });
        return validObj;

    }

    var arrayOfFieldNames = ['user_name'];
    var arrayOfValidVars = [];
    var count = 0;
    for (var i = 0; i < arrayOfFieldNames.length; i++){
        arrayOfValidVars[count] = new LiveValidation(arrayOfFieldNames[i], {onValid: function(){}});
        arrayOfValidVars[count].add(Validate.Format, {
            pattern: /^[a-zA-Z0-9._-]*$/i,
            failureMessage: 'Darf nur Buchstaben, Ziffern, "_", "-" und Punkt enthalten'
        })
        count++;
    }
    var user_name_pat = setValidationPattern('user_name', /^[a-zA-Z0-9._-]*$/i, 'Darf nur Buchstaben, Ziffern, "_", "-" und Punkt enthalten');
    var vorname = setValidationPattern('vorname', /^([A-Z]){1}([a-z\s-])*$/, 'Darf nur Buchstaben, Leerzeichen und "-" enthalten. Grossbuchstabe am Anfang');
    var nachname = setValidationPattern('nachname', /^([A-Z]){1}([a-z\s-])*$/, 'Darf nur Buchstaben, Leerzeichen und "-" enthalten. Grossbuchstabe am Anfang');
    var ablaufdatum = setValidationPattern('ablaufdatum', /^(0[1-9]|[12][0-9]|3[01])[/](0[1-9]|1[012])[/](19|20)\d\d$/i, 'Datum falsch eingegeben? Bitte folgendes Format benutzen: dd/mm/yyyy, z.B.22/03/2012');
    var user_name = new LiveValidation('user_name', {onValid: function(){}});
    user_name.add(Validate.Presence, {failureMessage: 'Name darf nicht leer sein!'});

    var mail = new LiveValidation('email', {onValid: function(){}});
    mail.add( Validate.Email);

    var password2 = new LiveValidation("password_hash2", {onValid: function(){}});
    password2.add( Validate.Confirmation, {
        match: 'password_hash',
        failureMessage: "Passwort muss \u00FCbereinstimmen"
    });

    return true; //LiveValidation.massValidate( [ user_name_pat, ablaufdatum, mail, password2, vorname, nachname] );
}

function array_diff_key (arr1) {
    var argl = arguments.length,
        retArr = {},
        k1 = '',
        i = 1,
        k = '',
        arr = {};

    arr1keys: for (k1 in arr1) {
        for (i = 1; i < argl; i++) {
            arr = arguments[i];
            for (k in arr) {
                if (k === k1) {
                    // If it reaches here, it was found in at least one array, so try next value
                    continue arr1keys;
                }
            }
            retArr[k1] = arr1[k1];
        }
    }
    return retArr;
}

var _editable_user_params_array = array_diff_key(_empty_user_data_array, _not_editable_user_params_array);

function loadContentCallback(data) {

        _user_data_array = data;

        var ulhtml = "<ul>";
        for(var i = 0; i < data.length; i++) {
		ulhtml += "<li><button class='user_button' id="+ i +">"+data[i].user_name+"</button></li>";
	}
        ulhtml += "<li><button id='addNewUser'>Add new user</button></li>";
        ulhtml += "</ul>";
	$("#userList").html(ulhtml);

        loadFilePermTree();

        reInitUi();
}

function loadFilePermTree(){
    mainPath  = "<?php echo ($_SERVER['DOCUMENT_ROOT']); ?>";
    addFolderTree();
}

function addFolderTree(){

	$('#userPermission').fileTree({ root: '/', rechte: '1', expandCallBack: function(){loadCheckBoxes();fillPermissionSubCheckBox();}}, function(file, folder) {
		var rel = "";
		if (file != null){
			rel = file;
                        //toggle checkbox on click on file
                        var selectdChkBx = $('#userPermission input[value="'+rel+'"]:checked');
                        if(selectdChkBx.length != 0){
                            selectdChkBx.removeAttr('checked');
                        }else{
                            $('#userPermission input[value="'+rel+'"]').attr('checked', 'true');
                        }

		}else if(folder != null){
			//rel = folder;
		}
	});
}

function loadCheckBoxes(){
    $("#userPermission input").val(_check_boxes_status);
}

function fillFieldsWithData(dataArray){
    var html = "";
    for(var element in dataArray){
        switch (element) {
                case "permission":
                    _check_boxes_status = dataArray[element].split("|");
                    $("#userPermission input").val(_check_boxes_status);
                    fillPermissionSubCheckBox();
                    break;
                case "password_hash":
                    html += createOptionHtml(_user_params_full[element].name, "password_hash", "", 0, "password");
                    html += createOptionHtml(_user_params_full[element].name+" wiederholen", "password_hash2", "", 0, "password");
                    break;
                case "rolle":
                    html += createDropBoxOptionHtml(_user_params_full[element].name, element, _user_roles_array, dataArray[element]);
                    break;
                case "bedienermodus":
                    html += createDropBoxOptionHtml(_user_params_full[element].name, element, _user_modus_array, dataArray[element]);
                    break;
                case "letzter_login": case "erstellungsdatum":  case "ablaufdatum":
                    if(dataArray[element] > 0){
                        var datum = new Date(dataArray[element]*1000);
                        var datumString = (datum.getDate()<10 ? "0"+(datum.getDate()) : datum.getDate()) + "/"+ (datum.getMonth()<10 ? "0"+(datum.getMonth()+1):datum.getMonth()+1) +"/"+datum.getFullYear(); //datum.toLocaleString();
                        if(element == "letzter_login"){datumString = datum.toLocaleString();} // mit time anzeigen
                    }else{
                        var datumString = "";
                    }
                    html += createOptionHtml(_user_params_full[element].name, element, datumString);
                    break;
                case "zusatzrechte":
                    break;
                default:
                    html += createOptionHtml(_user_params_full[element].name, element, dataArray[element]);
                    break;
            }
    }
    html += "<br><hr/>";

    $('#userOptions').html(html);

    //make not editable READONLY, or disabled?
    $.each($('#userOptions :input'), function(i, obj){
        if($(obj).attr('name') in _not_editable_user_params_array){
            $(obj).attr('disabled', true);
        }
    });


    //set datepicker for some of elements.  Bugged. need to dynamically recreate on focus.
    $('#userOptions :input[name="ablaufdatum"]').live('focus', function(){
        $(this).datepicker( "destroy" );
        var date = $.datepicker.parseDate('dd/mm/yy', $(this).val());
        $(this).datepicker();
        $(this).datepicker( "setDate" , date );
        $(this).datepicker( "option", "dateFormat", "dd/mm/yy" );

    //    $('#userOptions :input[name="ablaufdatum"]').datepicker('resfresh');
        $(this).datepicker('show');
    });
}

function reInitUi(){

    //bug by reuse .button() multiple spans..
    $('button').not($('span').parent('button')).button();

    $('button').button('disable');
    $('button').button('enable');

}


function addContentToElement(div, content){
    var tmpHtmlUO = div.html();
    tmpHtmlUO += content;
    div.html(tmpHtmlUO);
    reInitUi();
}

function createOptionHtml(optionName, htmlNameParam, wert, disParam, type){
    var disabled = (disParam == null || !disParam)? "" : "disabled";
    var htmlName = (htmlNameParam == null)? optionName: htmlNameParam;
    var type = (type == null)? "text" : type;
    return '<p><label>'+optionName+'</label><input class="userOptionsElement" '+disabled+' type="'+ type +'" id="'+htmlName+'" name="'+htmlName+'" value="'+wert+'"></p>';
}

//args: optionName - label name
//optionHtmlNameParam - optional <select> name
//listArray - array of select list elems
//selectedElem - selected element (name)
function createDropBoxOptionHtml(optionName, optionHtmlNameParam, listArray, selectedElem){
    var optionHtmlName = (optionHtmlNameParam == null) ? optionName : optionHtmlNameParam;
    var html = '<p><label>'+optionName+'</label>';
    var value = "";
    var selected = "";
    html += '<select name="'+ optionHtmlName +'" class="userOptionsElement" size="1">';
    for(var elem in listArray){
        value = listArray[elem]['value'];
        selected = (value != selectedElem)?'':'selected="true"';
        html += '<option '+ selected +' value="'+value+'">'+ listArray[elem]['name']+'</option>';
    }
    html += '</select></p>';
    return html;
}

function createButtonHtml(buttonName, label){
    var html = '<button class="temp_buttons" id='+buttonName+'>'+ label +'</button>';
    return html;
}
function clearTempButtons(){
    $('.temp_buttons').remove();
}

//spaghetticode function? Oo
function fillPermissionSubCheckBox(pfad, checked){
    function removeCheckBoxParentRek(pfad, count){
        if(pfad.length == 0){return;};
        if(count>1000){return;}
        var parent = verzeichniss(pfad);
        $('#userPermission input[value="'+parent+'"]').removeAttr('checked');
            //for global array of chkbx state
            saveChkBoxStatus(parent, false);
            $("#userPermission input[value^='"+ parent +"']:checked").each(function(i, obj){
                saveChkBoxStatus($(obj).val(), true);
            });
        if(parent != "/"){
            parent = verzeichniss(parent.replace(/\/$/, ""));
            removeCheckBoxParentRek(parent, count+1);
        }
    }
    function addCheckBoxParentRek(pfad, count){
        if(pfad.length == 0){return;};
        if(count>1000){return;}
        var verz = verzeichniss(pfad);
        if($("#userPermission input[value^='"+ verz +"']").not(':checked').length == 1){
            $('#userPermission input[value="'+verz+'"]').attr('checked', 'true');
            saveChkBoxStatus(verz, true);
        }
        if(verz != "/"){
            verz = verzeichniss(verz.replace(/\/$/, ""));
            addCheckBoxParentRek(verz, count+1);
        }

    }
    //checked or just refresh (no args)
    if(checked || pfad == null){
        var checkedArray = [];
        //checked folder -> fill array with it, and check subfolders/files
        if(pfad != null  && pfad.match(/.*\/$/)){
            checkedArray.push(pfad);
        //default, without params.
        }else if(pfad == null){
            $.each($("#userPermission input:checked"), function(i, obj){
                checkedArray.push($(obj).val());
            });
        }
        //even checking subfolders/files
        $.each($("#userPermission input"), function(i, obj){
            if(hasparent($(obj).val(),checkedArray)){
                $(obj).attr('checked', 'true');
            }
        });
        //test if all files are checked for the parent folder. In case its true, check parent
        addCheckBoxParentRek((pfad == null)?"":pfad,0);
    //uncheck
    }else{
        //uncheck folder -> uncheck all files contained in the folder
        if(pfad.match(/.*\/$/)){
            $('#userPermission input[value^="'+pfad+'"]').removeAttr('checked');
        }
        removeCheckBoxParentRek(pfad, 0);
    }
}



function removeUserCallback(data) {
	alert(data);
	location.reload();
}

function updateUserCallback(data) {
	alert(data);
	location.reload();
}

function createUserCallback(data) {
	alert(data);
	location.reload();
}

function setPanelScroll() {
	var winHeight = $(window).height();
	var panelHeight = winHeight - 208; // TODO
	$("#usermanager").css("height", panelHeight + "px");
}

function checkForm(pwcheck){
    var capitaliseFirstLetter = function(field)
    {
        var string = field.val();
        field.val( string.charAt(0).toUpperCase() + string.slice(1));
    }

    capitaliseFirstLetter($("#usermanager input[name='vorname']"));
    capitaliseFirstLetter($("#usermanager input[name='nachname']"))

    var passWord = $("#usermanager input[name='password_hash']").val();


    if(pwcheck != null && pwcheck){
        if(passWord == "") {
            alert("Bitte Passwort eingeben!");
            return false;
        }
    }


    var permission = readPermissions();
    if(permission == ""){
        if(!confirm("Keine Zugriffsrechte zugewiesen, trotzdem weiter?")) {
            return false;
        }
    }

    if (!load_validation()) {
        alert("Bitte die Fehler korrigieren")
        return false;
    }

    return true;
}

function hasparent(path, permission){
    var retVal = false;
    var verz, regexpparent;
    for(var i =0, j = permission.length; i <j; i++){
        if(permission[i].match(/.*\/$/i)){
            verz = permission[i];
            verz = verz.replace(/\//g, '\\/');
            regexpparent = eval('/^'+verz+'/i');

            if(path.match(regexpparent)){
              retVal = true;
              break;
            }
        }
    }
    return retVal;
}

function verzeichniss(path){
    var str = path + '';
    var slash = str.lastIndexOf('/');
    return str.substr(0, slash+1);
}

function readPermissions(permArray){
    var permission = [];
    //nichts uebergeben? die sichtbare checkbox's auslesen
    if(permArray == null){
        $("#userPermission input:checked").each(function() {
            if(!hasparent($(this).val(), permission)){
                permission.push($(this).val());
            }
        });
    }else{
        $.each(permArray, function(i, elem) {
            if(!hasparent(elem, permission)){
                permission.push(elem);
            }
        });
    }


    return permission.join("|");
}


function readInput(){
    var arrValues = _editable_user_params_array;
    var serArr = [];

    $.each($("#userOptions :input"), function(i, obj){
        if($(obj).attr('disabled') == null){
            serArr[$(obj).attr('name')] = $(obj).val();
        }
    })
    serArr['permission'] = readPermissions(_check_boxes_status);

    if(serArr['password_hash'] != undefined && serArr['password_hash'] != null && serArr['password_hash'] != ""){
        serArr['password_hash'] = $.md5(serArr['password_hash']);
    }

    //datum in ms umwandeln
    serArr['ablaufdatum'] = Math.round(($.datepicker.parseDate('dd/mm/yy', serArr['ablaufdatum'])/1000)); //Date.parse(serArr['ablaufdatum'])/1000);


    for(var elem in arrValues){
        arrValues[elem] = (serArr[elem] == null) ? "" : serArr[elem];
    }
    return arrValues;
}

//checks if something changed by the user. wert?-> setter.
function checkInputChange(wert){
    if(wert == null){
        if(_somethingChanged == true){
            if(confirm(unescape("Daten ge%E4ndert%2C wirklich verlassen%3F"))){
                return false;
            }
            return true;
        }
    }else{
        if(wert){
            _somethingChanged = true;
        }else{
            _somethingChanged = false;
        }
    }
}

//save/remove value from global chkBoxSTATUS array.
function saveChkBoxStatus(value, chkYesNo){
        if(chkYesNo){
            //add pfad, delete childs
            _check_boxes_status.push(value);
        }else{
            //remove pfad
            _check_boxes_status = $.grep(_check_boxes_status, function(elem) {
                return elem != value;
            });
        }
        //remove childs
        _check_boxes_status = $.grep(_check_boxes_status, function(elem) {
            return !(elem.substring(0, value.length) == value && elem != value);
        });
        //make uniq.
        $.unique(_check_boxes_status);

        //if no elements, set "", if more then 1 remove ""
        if(_check_boxes_status.length == 0){
            _check_boxes_status.push("");
        }else if(_check_boxes_status.length > 1 ){
            _check_boxes_status = $.grep(_check_boxes_status, function(elem) {
                return elem != "";
            });
        }
    }

//select a menu button, unselect others
function selectMenu(element_button){
    $.each($("#userList button"), function(){
           $(this).blur();
           $(this).find('span').removeClass('buttonMenuSelected');
          });
    element_button.find('span').addClass('buttonMenuSelected');
}


/* ---------- Here comes jQuery: ---------- */
$(document).ready(function() {
    $("button").button();

    $.getJSON("app/edit_user.php?r=" + Math.random(), {
        "json_oper": "get_users"
    }, loadContentCallback);

    $("#userPermission input").live('change', function(){

        var checkedYesNo = $(this).prop('checked');
        var thisRef = $(this).val();
        saveChkBoxStatus(thisRef, checkedYesNo );
        fillPermissionSubCheckBox(thisRef, checkedYesNo);

    });

    $("#userList .user_button").live('click', function(){
        if(checkInputChange()){return;}
        var userArray = _user_data_array[$(this).attr("id")]
        fillFieldsWithData(userArray);
        _currentValues['user'] = userArray['user_name'];
        //ui bug ? ..
        addContentToElement($('#userOptions'), createButtonHtml('updateUser', 'Save') + createButtonHtml('removeUser', 'Delete User'));
        //addContentToElement($('#userOptions'), createButtonHtml('removeUser', 'Delete User'));
        checkInputChange(false);

        selectMenu($(this));
    });

    $("#userList #addNewUser").live('click',function() {
        if(checkInputChange()){return;}
        fillFieldsWithData(_empty_user_data_array);
        clearTempButtons();
        addContentToElement($('#userOptions'), createButtonHtml('createUser', 'Save New User'));
        _currentValues['user'] = "";
        checkInputChange(false);
        selectMenu($(this));
    });

    $("#usermanager #createUser").live('click',function() {
        if(!checkForm(true)){
            return;
        }
        var userName = $("#usermanager input[name='user_name']").val();
        var params = readInput();
        params = JSON.stringify(params);

        $.post("app/edit_user.php",
        {
           "json_oper": "create_user",
           "user"     : userName,
           "params"   : params
        }, createUserCallback);

    });

    $("#usermanager #updateUser").live('click',function() {
        if(!checkForm()){
            return;
        }
        var userName = _currentValues['user'];

        if(confirm(unescape('Den Benutzer: \"'+ userName + '" aktualisieren?'))){
            var params = readInput();
            if(params['password_hash'] == ""){
                delete params['password_hash'];
            }
            params = JSON.stringify(params);

            $.post("app/edit_user.php",
            {
               "json_oper": "update_user",
               "user"     : userName,
               "params"   : params
            }, updateUserCallback);
        }


    });

    $("#usermanager #removeUser").live('click',function() {
        var userName = _currentValues['user'];
        if(confirm(unescape('Den Benutzer: \"'+ userName + '" l%F6schen?'))){
              if(admin_uname == userName) {
                alert("You cannot remove SERVER_ADMIN!");
                return;
            }
            $.post("app/edit_user.php", {
                "json_oper": "remove_user",
                "user_name": userName
            }, removeUserCallback);
        }
    });

    $('#usermanager').find(':input').live('change', function(){
       if(_currentValues['user'] != null){
           if( _currentValues['user'].length > 0){
               _somethingChanged = true;
           }
       }
    });

    // help
    $("#helpHand a").click(function() {
        if(helpText == "") {
            $.get("app/get_help.php?r=" + Math.random(), {
                "page_name": "user_manager"
            }, function(rdata){
                helpText = rdata;
                $("#helpCont").html(helpText);
                $("#helpCont").slideToggle("fast");
            });
        } else {
            $("#helpCont").slideToggle("fast");
        }
    });

    $(window).resize(function() {
        setPanelScroll();
    });
    setPanelScroll();
});
</script>
</head>

<body id="bd_User">
<div id="wrapper">
	<h1 id="header"><?php echo($ne2_config_info['app_title']); ?></h1>
	<div id="navBar">
		<?php require('common_nav_menu.php'); ?>
	</div>

    <div id="contentPanel1222">
        <?php
        // help
        if (has_help_file()) {
            ?>
            <div id="helpCont">.</div>
            <div id="helpHand"><a href="javascript:;">Hilfe</a></div>
            <?php
        }
        ?>
        <div id="usermanager" >
            <div id="userList"></div>
            <div id="userPermission"></div>
            <div id="userOptions"></div>
        </div>
    </div>

<?php require('common_footer.php'); ?>
</div>
</body>

</html>
