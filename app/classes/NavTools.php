<?php

/**
 * NavEditor2 Tools Class
 * Functions collection
 * @uses config.php - global variables
 */
class NavTools {

    /**
     * include js und css files in html. from JS and CSS directory
     * there is an option to add default includes set like jQuery files etc..
     *
     * @global array $ne2_config_info
     * @param String $Filenames<p>
     * Filenames, single filename or array of strings.
     * Additional html string (parameter?) after comma.<br />
     * example includeHtml("folder/file.css, version=2", "some.js")
     * </p>
     * @return String like <link rel="stylesheet" ... / <script src=" ...
     * for each argument
     * <p>with no params "default" files will be loaded  </p>
     * <p>*html source will be full url to the file  </p>
     */
    public static function includeHtml(/* ARGS */) {
        global $ne2_config_info;
        $retString = '';

        //if no arguments, set to "default".
        if (func_num_args() == 0) {
            $arrayFiles = Array('default');
        //else if first argument is an array, then use args of this array
        } elseif (is_array(func_get_arg(0))) {
            $arrayFiles = func_get_arg(0);
        //else use each argument
        } else {
            $arrayFiles = func_get_args();
        }

        //for each argument..
        foreach ($arrayFiles as $file) {
            //check for default sets
            switch (strtolower($file)) {
                case "default":
                    $retString .= self::includeHtml($ne2_config_info['default_includes_js_css']);
                    continue;
            }
            //split file - params
            $file_splitted_array = explode(",", $file, 2);
            if (sizeof($file_splitted_array) == 1) {
                $file_splitted_array[1] = "";
            }

            $file_only = $file_splitted_array[0];
            $file_params = $file_splitted_array[1];
            //get extension
            $ext = $file_only;
            $ext = parse_url($ext, PHP_URL_PATH);
            $ext = pathinfo($ext, PATHINFO_EXTENSION);

            switch (strtolower($ext)) {
                case "js":
                    $path = $ne2_config_info['ne2_url'] . $ne2_config_info['js_folder_name'] . "/" . $file_only;
                    $retString .= "<script type=\"text/javascript\" src=\"" . $path . $file_params . "\"></script>\n";
                    break;
                case "css":
                    $path = $ne2_config_info['ne2_url'] . $ne2_config_info['css_folder_name'] . "/" . $file_only;
                    $retString .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $path . $file_params . "\">\n";
            }
        }

        return $retString;
    }

    /**
     * get execution time in seconds at current point of call
     * @return float Execution time at this point of call
     */
    public static function get_execution_time() {
        static $microtime_start = null;
        if ($microtime_start === null) {
            $microtime_start = microtime(true);
            return 0.0;
        }
        return microtime(true) - $microtime_start;
    }

    /**
     * save execution time to logfile, in seconds at current point of call
     */
    public static function save_execution_time($message = "", $logfile = "", $debug = -1) {
        global $ne2_config_info;
        if (strcmp($logfile, "") == 0) {
            $logfile = $ne2_config_info['debug_execution_file'];
        }
        if ($debug == -1) {
            $debug = $ne2_config_info['debug_time'];
        }


        if (isset($debug) && ($debug < 1 )) {
            return;
        }
        $nowtime = self::get_execution_time();
        if (isset($message)) {
            $nowtime = $nowtime . "\t" . $_SERVER['SCRIPT_FILENAME'] . "\t" . $message . "\n";
        }
        file_put_contents($logfile, $nowtime, FILE_APPEND | LOCK_EX);
        return;
    }

    /**
     * get server admin name
     * @return String
     */
    public static function getServerAdmin() {
        $server_admin = $_SERVER['SERVER_ADMIN'];
        $server_admin = explode('@', $server_admin);
        $server_admin = $server_admin[0];
        return $server_admin;
    }

    /**
     * Filters not allowed symbols by any string
     * @param String $string <p> Input String</p>
     * @return String <p>Returns filtered String</p>
     */
    public static function filterSymbols($string) {
        global $ne2_config_info;
        $string = str_replace($ne2_config_info['symbols_being_replaced'], $ne2_config_info['symbols_replacement'], $string);
        $string = preg_replace($ne2_config_info['regex_removed_symbols'] . 'u', '', $string); //u fuer UTF-8 symbole ersetzung
        return $string;
    }

    /**
     * Checks if $haystack starts with $needle
     * @param String $haystack string to check
     * @param String $needle starts with it
     * @param Boolean $case case sensive
     * @return Boolean True if $haystack starts with $needle
     */
    public static function startsWith($haystack, $needle, $case = TRUE) {

        if ($case) {
            return !strncmp($haystack, $needle, strlen($needle));
        }

        return !strncasecmp($haystack, $needle, strlen($needle));
    }

    /**
     * Checks if $haystack ends with $needle
     * @param String $haystack string to check
     * @param String $needle ends with it
     * @param Boolean $case case sensive
     * @return Boolean True if $haystack ends with $needle
     */
    public static function endsWith($haystack, $needle, $case = TRUE) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        if ($case) {
            return (substr($haystack, -$length) === $needle);
        }

        return (strcasecmp(substr($haystack, -$length), $needle) === 0);
    }

    /**
     * Filters path, in case it is over DOCUMENT_ROOT path
     * @param String $path Path to test
     * @return String $path or empty string in case the path is over the root
     */
    public static function root_filter($path) {
        $ret_path = "";
        $root_path = $_SERVER['DOCUMENT_ROOT'];
        $path = self::simpleResolvePath($path);
        if (strlen($path) >= strlen($root_path)) {
            if (self::startsWith($path, $root_path)) {
                $ret_path = $path;
            }
        }
        return $ret_path;
    }

    /**
     * Simple resolve path func. /cgi-bin/feed/../dir  => /cgi-bin/dir
     * @param string $path
     * @return string
     */
    public static function simpleResolvePath($path) {
        return preg_replace('/\w+\/\.\.\//', '', $path);
    }

    /**
     * If $variable is not set, returns $default, otherwise $variable
     * @link https://wiki.php.net/rfc/ifsetor
     * @param type $variable variable to test
     * @param type $default value to return if $variable is null
     * @return mixed $default or $variable depends on isset($variable) test
     */
    public static function ifsetor(&$variable, $default = null) {
        if (isset($variable)) {
            $tmp = $variable;
        } else {
            $tmp = $default;
        }
        return $tmp;
    }

    /**
     * Tests if an Array is associative
     * http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-numeric
     * @param array $array
     * @return bool
     */
    public static function is_assoc($array) {
        if (!is_array($array))
            return FALSE;

        return (bool) count(array_filter(array_keys($array), 'is_string'));
    }

    /**
     * log errors/warnings to specified file
     * @global array $ne2_config_info
     * @param string $error_text
     */
    public static function error_log($error_text, $callerFunc = "") {
        global $ne2_config_info;
        if (!empty($callerFunc)) {
            $callerFunc = $callerFunc . ': ';
        }
        error_log(date('Y-m-d H:m') . ' - ' . $callerFunc . $error_text . "\n", 3, $ne2_config_info['error_log_file']);
    }

}

?>
