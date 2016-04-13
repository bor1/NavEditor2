/*
 * NavEditor JavaScript Tools
 */

var NavTools = new function(){
    if(!jQuery){return false;}
    var settings = {}; //settings
    var self = this;

    /**
     * returns ifnullvalue if 'value' is null or undefined and 'value' otherwise
     * @param value value to test for null or undefined
     * @param ifNotSetvalue value to return in case 'value' is null or undefined
     * @return mixed
     */
    this.ifsetor = function(value, ifNotSetvalue){
        if(value === undefined || value === null){
            return ifNotSetvalue;
        }
        return value;
    };

    /**
     * Sets settings, with 's' elements, or default.
     * @param [s] - settings object, with setting
     */
    this.set_settings = function(s){
        if( !s ){var s = {};}
        settings.current_host = location.protocol + "//" + location.host + "/";
        settings.nav_editor_path = self.ifsetor(s.nav_editor_path, 'vkdaten/tools/NavEditor2/');
        settings.nav_editor_path = self.ifsetor(s.nav_editor_path, 'vkdaten/tools/NavEditor2/');
        settings.ajax_handler_path = self.ifsetor(s.ajax_handler_path, 'app/ajax_handler.php');
        settings.ajax_handler_fullpath = settings.current_host + settings.nav_editor_path + settings.ajax_handler_path;
    };

    /**
     * Settings Getter
     * @return Object - settings object
     */
    this.get_settings = function(){
        return settings;
    };

    /**
     * request 'phpFileName' php file, his function 'phpFunction' with args: 'args'<br/>
     * call 'fnCallback' function after.
     */
    this.call_php = function(phpFileName, phpFunction, args, fnCallback){

        $.post(settings.ajax_handler_fullpath, {
            "file": phpFileName,
            "function": phpFunction,
            "args": args
        }, fnCallback);
    };


    /**
     * escapes html
     * @link http://stackoverflow.com/questions/1787322/htmlspecialchars-equivalent-in-javascript
     */
    this.escapeHtml = function(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    /**
     * encode value to html
     * @link http://stackoverflow.com/questions/1219860/javascript-jquery-html-encoding
     */
    this.htmlEncode = function(value){
        //create a in-memory div, set it's inner text(which jQuery automatically encodes)
        //then grab the encoded contents back out.  The div never exists on the page.
        return $('<div/>').text(value).html();
    };

    /**
     * decode html value
     * @link http://stackoverflow.com/questions/1219860/javascript-jquery-html-encoding
     */
    this.htmlDecode = function (value){
        return $('<div/>').html(value).text();
    };

    /**
     * add slashes to string
     * @link http://phpjs.org/functions/addslashes/
     */
    this.addslashes = function(str){
        return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
    };

    /**
     * strip slashes from string
     * @link http://phpjs.org/functions/stripslashes/
     */
    this.stripslashesh = function (str) {
        return (str + '').replace(/\\(.?)/g, function (s, n1) {
            switch (n1) {
                case '\\':
                    return '\\';
                case '0':
                    return '\u0000';
                case '':
                    return '';
                default:
                    return n1;
            }
        });
    };





    //set default settings
    this.set_settings();

//    return this;
};

//var NavTools = new NavTools();