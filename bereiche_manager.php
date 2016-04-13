<?php
require_once('auth.php');
require_once('app/config.php');

// help
function has_help_file() {
    global $ne2_config_info;
    $help_file = $ne2_config_info['help_path'] . 'bereich_manager' . $ne2_config_info['help_filesuffix'];
    return file_exists($help_file);
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

        <title>Bereich management - <?php echo($ne2_config_info['app_titleplain']); ?></title>

        <?php
        echo NavTools::includeHtml('default',
                'jquery-ui-1.8.18.custom.min.js',
                'jqueryui/ne2-theme/jquery-ui-1.8.17.custom.css',
                'naveditor2.js',
                'jquery.md5.js',
                'livevalidation_standalone.compressed.js',
                'live_validation.css',
                'nav_tools.js'
        )
        ?>
        <style type="text/css">
            #content_bereich_manager {
                padding: 0 0.5em 0 0;
                margin: 0 0 0.5em 0;
                float: right;
                width: 100%;
            }

            #bereichmanager {
                width: 100%;
                min-height: 150px;
                max-height: 100%;
                min-width:  640px;
            }

            #bereichmanager>div {
                border: 1px solid silver;
            }

            #bereichmanager #bereichList{
                width: 10%;
                height: 100%;
                float: left;
                overflow: auto;
                margin: 0 0 0 1em;
                padding: 1px;
            }

            #bereichmanager #bereichSettings{
                padding: 2px 2px 2px 1em;
                margin: 0 22% 0 12%;
                overflow: auto;
                height: 100%;

            }


            #bereichmanager #bereichList button{
                width: 100%;
            }

            #bereichmanager .temp_buttons{
                width: 10em;
                height: 2.5em;
                margin: 10px;
                /*font-size: small;*/
            }

            #bereichmanager .bereichSettingsElement{
                width: 320px;
                margin-left: 30px;
            }

            #bereichmanager label{
                display: inline-block;
                vertical-align: middle;
                alignment-adjust: middle;
                width: 10em;
            }
            #bereichList li button .buttonMenuSelected{
                color: #f4f954;
                font-weight: bolder;
            }

        </style>

        <script type="text/javascript">

            var helpText = "";

            /**
             * array with data for current selected item. Will be dinamically created by item selection
             */
            var _bereich_data_array = [];

            /**
             * associative array with all settings without values
             */
            var _empty_bereich_data_array = [];

            /**
             * array with all possible settings-names
             */
            var _bereich_params_full = [];

            /**
             * array to save some dinamically values
             */
            var _currentValues = [];

            /**
             * associative array/object with all information about any possible setting
             */
            var _bereich_params_data = $.parseJSON('<?php echo(json_encode($g_bereich_settings['bereich_settings'])); ?>');

            //generate _bereich_params_full and _empty_bereich_data_array
            for(var bereichName in _bereich_params_data){
                _bereich_params_full.push(bereichName);
                _empty_bereich_data_array[bereichName] = '';
            }

            /**
             * Mark if input changed
             */
            var _somethingChanged = false;

            /**
             * Validation function for input
             * @return bool success of validation
             */
            function load_validation(){
                //TODO bessere validation

                var arrayToValidate = [];

                //helper func.
                var setValidationPattern = function(field, pattern, errormsg){
                    var validObj = new LiveValidation(""+field, {onValid: function(){}});
                    validObj.add(Validate.Format, {
                        pattern: pattern,
                        failureMessage: errormsg
                    });
                    return validObj;

                }
                //fuer alle not empty settings, validation object erstellen, in arrayToValidate hinzufuegen
                for(var field in _bereich_params_data){
                    if(_bereich_params_data[field]['notempty'] == true){
                        var tmpValObj = new LiveValidation(field, {onValid: function(){}});
                        tmpValObj.add(Validate.Presence, {failureMessage: _bereich_params_data[field]['name']+' darf nicht leer sein!'});
                        arrayToValidate.push(tmpValObj);
                    }
                }

                //extra pattern fuer name
                var bereich_name_pat = setValidationPattern('name', /^[a-zA-Z0-9-]*$/i, 'Darf nur Buchstaben, Ziffern und "-" enthalten');
                arrayToValidate.push(bereich_name_pat);


                //validate alles
                return LiveValidation.massValidate( arrayToValidate );
            }




            /**
             * erstellt felder und fuellt die mit Data dataArray
             *
             * @param Object dataArray assoziatives array mit data ueber aktuelles Bereich<br/>
             * format: siehe config_bereichseditor.php
             */
            function fillFieldsWithData(dataArray){
                var html = "";
                for(var element in dataArray){
                    //falls in parameters vorhanden..   (eigentlich muss immer der Fall sein!)
                    if($.inArray(element, _bereich_params_data)){
                        //info ueber diesen parameter
                        var parameterInfo = _bereich_params_data[element];
                        //fuer bestimmte parameter typen..
                        switch (parameterInfo.type) {
                            case 'memo':
                                html += generateSettingTextAreaHtml(parameterInfo.name, element, dataArray[element]);
                                break;
                            case 'values':
                                html += generateDropBoxSettingHtml(parameterInfo.name, element, parameterInfo.values, dataArray[element]);
                                break;
                            case 'text':
                            default:
                                html += generateSettingHtml(parameterInfo.name, element, dataArray[element]);
                                break;

                        }
                    }
                }
                html += "<br><hr/>";

                $('#bereichSettings').html(html);
            }



            /**
             * refresh UI elements
             */
            function reInitUi(){

                //bug by reuse .button() multiple spans..
                $('button').not($('span').parent('button')).button();

                $('button').button('disable');
                $('button').button('enable');

            }

            /**
             * adds content to div
             * @param Object div, jQuery reference to div element
             * @param string content html to add.
             */
            function addContentToElement(div, content){
                var tmpHtmlUO = div.html();
                tmpHtmlUO += content;
                div.html(tmpHtmlUO);
                reInitUi();
            }

            /**
             * generates textarea html element
             *
             * @param string settingName - label name
             * @param string settingHtmlNameParam - optional html name (id)
             * @param mixed value - value to display in textbox
             * @param bool disParam - [optional = 'disabled'] true if need to disable textbox
             */
            function generateSettingTextAreaHtml(settingName, settingHtmlNameParam, value, disParam){
                var disabled = (disParam == null || !disParam)? "" : "disabled";
                var htmlName = (settingHtmlNameParam == null)? settingName: settingHtmlNameParam;
                return '<p><label>'+settingName+'</label><textarea cols="37" rows="3" class="bereichSettingsElement" '
                    + disabled + ' id="'+htmlName+'" name="'+htmlName+'">'+NavTools.escapeHtml(value)+'</textarea></p>';
            }


            /**
             * generates input html element
             *
             * @param string settingName - label name
             * @param string settingHtmlNameParam - optional html name (id)
             * @param mixed value - value to display in textbox
             * @param bool disParam - [optional = 'disabled'] true if need to disable textbox
             * @param string type - [optional = 'text'] type of input. Default:
             */
            function generateSettingHtml(settingName, settingHtmlNameParam, value, disParam, type){
                var disabled = (disParam == null || !disParam)? '' : 'disabled';
                var htmlName = (settingHtmlNameParam == null)? settingName: settingHtmlNameParam;
                var type = (type == null)? 'text' : type;
                return '<p><label>'+settingName+'</label><input class="bereichSettingsElement" '
                    +disabled+' type="'+ type +'" id="'+htmlName+'" name="'+htmlName+'" value="'+NavTools.escapeHtml(value)+'"></p>';
            }

            /**
             * generates DropBox html element
             * @param string settingName - label name
             * @param string [optional] settingHtmlNameParam - html control name
             * @param array listArray - associative array of select-list elems
             * @param string selectedElem - selected element (value)
             */
            function generateDropBoxSettingHtml(settingName, settingHtmlNameParam, listArray, selectedElem){
                var settingHtmlName = (settingHtmlNameParam == null) ? settingName : settingHtmlNameParam;
                var html = '<p><label>'+settingName+'</label>';
                var name = "";
                var selected = "";
                html += '<select name="'+ settingHtmlName +'" id="'+settingHtmlName+'" class="bereichSettingsElement" size="1">';
                for(var elem in listArray){
                    name = listArray[elem];
                    selected = (elem != selectedElem)?'':'selected="true"';
                    html += '<option '+ selected +' value="'+elem+'">'+ name+'</option>';
                }
                html += '</select></p>';
                return html;
            }

            /**
             * generates html for dinamic menu button
             * @param string buttonName - name of button
             * @param string label - button label
             */
            function createButtonHtml(buttonName, label){
                var html = '<button class="temp_buttons" id='+buttonName+'>'+ label +'</button>';
                return html;
            }

            /**
             * removes .temp_buttons
             */
            function clearTempButtons(){
                $('.temp_buttons').remove();
            }

            /**
             * callback function after remove
             */
            function removeBereichCallback(data) {
                alert(unescape((data == true)?'Erfolgreich gel%F6scht':data));
                location.reload();
            }

            /**
             * callback function after update
             */
            function updateBereichCallback(data) {
                alert(unescape((data == true)?'Erfolgreich ge%E4ndert':data));
                location.reload();
            }

            /**
             * callback function after add_new
             */
            function createBereichCallback(data) {
                alert(unescape((data == true)?'Erfolgreich erstellt':data));
                location.reload();
            }

            /**
             * content callback function.<br/>
             * creates dinamically html in settings area
             */
            function loadContentCallback(data) {

                _bereich_data_array = $.parseJSON(data);

                var ulhtml = "<ul>";

                for (var key in _bereich_data_array) {
                    if (_bereich_data_array.hasOwnProperty(key) && key != 'undefined') {
                        ulhtml += '<li><button class="bereich_button" id="'+ key +'">'+key+'</button></li>';
                    }
                }
                ulhtml += "<li><button id='addNewBereich'>Neuer Bereich</button></li>";
                ulhtml += "</ul>";
                $("#bereichList").html(ulhtml);

                reInitUi();
            }

            /**
             * resizes panel, adjust height for browser screen seize
             */
            function setPanelScroll() {
                var winHeight = $(window).height();
                var panelHeight = winHeight - 208; // TODO
                $("#bereichmanager").css("height", panelHeight + "px");
            }


            //TODO
            function checkForm(pwcheck){
//                var capitaliseFirstLetter = function(field)
//                {
//                    var string = field.val();
//                    field.val( string.charAt(0).toUpperCase() + string.slice(1));
//                }
//
//                capitaliseFirstLetter($("#bereichmanager input[name='vorname']"));
//                capitaliseFirstLetter($("#bereichmanager input[name='nachname']"))
//
//                var passWord = $("#bereichmanager input[name='password_hash']").val();
//
//
//                if(pwcheck != null && pwcheck){
//                    if(passWord == "") {
//                        alert("Bitte Passwort eingeben!");
//                        return false;
//                    }
//                }
//
//
//                var permission = readPermissions();
//                if(permission == ""){
//                    if(!confirm("Keine Zugriffsrechte zugewiesen, trotzdem weiter?")) {
//                        return false;
//                    }
//                }

                if (!load_validation()) {
                    alert("Bitte die Fehler korrigieren")
                    return false;
                }

                return true;
            }


            /**
             * Reads form input
             * @return array associative array with 'field name' => 'field value'
             */
            function readInput(){
                var newValues = {};

                //save each setting to array
                $.each($("#bereichSettings .bereichSettingsElement"), function(i, obj){
                    if($(obj).attr('disabled') == null){
                        newValues[$(obj).attr('name')] = $(obj).val();
                    }
                })

                return newValues;
            }


            /**
             * checks if something changed.
             * @param string wert if set then the func is setter
             */
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


            /**
             * select a menu button, unselect others
             */
            function selectMenu(element_button){
                $.each($("#bereichList button"), function(){
                    $(this).blur();
                    $(this).find('span').removeClass('buttonMenuSelected');
                });
                element_button.find('span').addClass('buttonMenuSelected');
            }


            /*-------- after document loaded do code: --------*/
            $(document).ready(function() {
                //make beautiful buttons
                $("button").button();

                //loading all info about areas
                NavTools.call_php('app/classes/BereichsManager.php', 'getAllAreaSettings',
                {},
                loadContentCallback);

                //all buttons to pick
                $("#bereichList .bereich_button").live('click', function(){
                    //ask by changes, prevent if needed
                    if(checkInputChange()){$(this).blur();return;}

                    var thisBereichName = $(this).attr("id");
                    var bereichArray = _bereich_data_array[thisBereichName]
                    fillFieldsWithData(bereichArray);
                    _currentValues['bereich'] = thisBereichName;
                    //ui bug ? ..
                    addContentToElement($('#bereichSettings'), createButtonHtml('updateBereich', 'Speichern') + createButtonHtml('removeBereich', 'L&ouml;schen'));
                    //addContentToElement($('#bereichSettings'), createButtonHtml('removeBereich', 'Delete Bereich'));
                    checkInputChange(false);

                    selectMenu($(this));
                });


                //btn create new
                $("#bereichList #addNewBereich").live('click',function() {
                    //ask by changes, prevent if needed
                    if(checkInputChange()){$(this).blur();return;}

                    fillFieldsWithData(_empty_bereich_data_array);
                    clearTempButtons();
                    addContentToElement($('#bereichSettings'), createButtonHtml('createBereich', 'Erstellen'));
                    _currentValues['bereich'] = "";
                    checkInputChange(false);
                    selectMenu($(this));
                });

                //btn save bei new
                $("#bereichmanager #createBereich").live('click',function() {
                    if(!checkForm(true)){
                        return;
                    }
                    var bereichName = $("#bereichmanager input[name='name']").val();
                    var params = readInput();
                    //                    params = JSON.stringify(params);

                    NavTools.call_php('app/classes/BereichsManager.php', 'addAreaSettings',
                    {
                        name: bereichName,
                        settings: params
                    },
                    createBereichCallback);
                });

                //btn save
                $("#bereichmanager #updateBereich").live('click',function() {
                    if(!checkForm()){
                        return;
                    }
                    var bereichName = _currentValues['bereich'];

                    if(confirm(unescape('Den Bereich: \"'+ bereichName + '" aktualisieren?'))){
                        var params = readInput()
                        //                        params = JSON.stringify(params);
                        NavTools.call_php('app/classes/BereichsManager.php', 'updateAreaSettings',
                        {
                            name: bereichName,
                            settings: params
                        },
                        updateBereichCallback);
                    }


                });

                //button delete bereich
                $("#bereichmanager #removeBereich").live('click',function() {
                    var bereichName = _currentValues['bereich'];
                    if(confirm(unescape('Den Bereich: \"'+ bereichName + '" l%F6schen?'))){
                        NavTools.call_php('app/classes/BereichsManager.php', 'deleteAreaSettings',
                        {
                            name: bereichName
                        },
                        removeBereichCallback);
                    }
                });

                //bind event 'change' to every input, to catch any changes, for checkInputChange() function
                $('#bereichmanager').find(':input').live('change', function(){
                    if(_currentValues['bereich'] != null){
                        if( _currentValues['bereich'].length > 0){
                            _somethingChanged = true;
                        }
                    }
                });

                // help
                $("#helpHand a").click(function() {
                    if(helpText == "") {
                        $.get("app/get_help.php?r=" + Math.random(), {
                            "page_name": "bereich_manager"
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

    <body id="bereich_manager">
        <div id="wrapper">
            <h1 id="header"><?php echo($ne2_config_info['app_title']); ?></h1>
            <div id="navBar">
                <?php require('common_nav_menu.php'); ?>
            </div>

            <div id="content_bereich_manager">
                <?php
// help
                if (has_help_file()) {
                    ?>
                    <div id="helpCont">.</div>
                    <div id="helpHand"><a href="javascript:;">Hilfe</a></div>
                    <?php
                }
                ?>
                <div id="bereichmanager" >
                    <div id="bereichList"></div>
                    <div id="bereichSettings"></div>
                </div>
            </div>

            <?php require('common_footer.php'); ?>
        </div>
    </body>

</html>
