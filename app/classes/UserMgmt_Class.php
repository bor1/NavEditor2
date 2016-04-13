<?php

/**
 * User Manager
 *
 * Requires config.php, auth.php!
 *
 * @global array $ne2_config_info
 *
 */
class UserMgmt {

    private $_user_data_file;
    private $_user_default_array;

    /**
     * Constructor <br/>
     * everytime on construct tests settings file for new format
     * @global array $ne2_config_info
     */
    public function __construct() {
        global $ne2_config_info;
        $this->_user_data_file = $ne2_config_info['app_path'] . 'data/' . $ne2_config_info['user_data_file_name'];
        $this->_user_default_array = get_ne2_user_params_simple();
        $this->checkForNewFormat();
    }

    /**
     * Get array of Users with all parameters
     * @param string $format [Optional = 'json'] what format, supports json
     * @return mixed array of users, or json string
     */
    public function GetUsers($format = 'json') {
        if (!file_exists($this->_user_data_file)) {
            touch($this->_user_data_file); // create file if not exists
            return NULL;
        }
        $jss = file_get_contents($this->_user_data_file);
        if ($jss == '') {
            return NULL;
        } else {
            if (strtolower($format) == 'json') {
                return $jss;
            } else {
                return json_decode(stripslashes($jss), TRUE);
            }
        }
    }

    /**
     * Get data of only one user $user_name in $format
     * @param string $user_name Username
     * @param string $format [Optional = 'json'] what format, supports json
     * @return mixed If user not found NULL, else user data in array format or json format
     */
    public function GetUser($user_name, $format = 'json') {
        $jss = $this->GetUsers();
        $users = json_decode(stripslashes($jss), TRUE);
        if (!is_null($users)) {
            foreach ($users as $udata) {
                if (strcmp($udata['user_name'], $user_name) == 0) {
                    if (strtolower($format) == 'json') {
                        return json_encode($udata);
                    } else {
                        return $udata;
                    }
                }
            }
        }
        return NULL;
    }

    /**
     * Add new user
     * @param string $user_name
     * @param array $user_params User settings array.
     * @return boolean
     */
    public function AddUser($user_name, $user_params) {
        $jss = file_get_contents($this->_user_data_file);
        $users = json_decode(stripslashes($jss), TRUE);
        if (!is_null($users)) {
            foreach ($users as $udata) {
                if (strcmp($udata['user_name'], $user_name) == 0) {
                    return FALSE;
                }
            }
        }
        if (is_null($users)) {
            $users = array();
        }

        $userArray = $this->_user_default_array;
        $this->fillByArray($userArray, $user_params);
        $userArray['user_name'] = $user_name;
        $userArray['erstellungsdatum'] = time();
        if (strcmp($user_name, NavTools::getServerAdmin()) == 0) { //admin name
            $userArray['rolle'] = "1000";
        }
        array_push($users, $userArray);
        $jss_to_write = json_encode($users);
        file_put_contents($this->_user_data_file, $jss_to_write);
        return TRUE;
    }


    /**
     * Macht Loginprozedur fuer benutzer $user_name
     * @param string $user_name Username
     * @param string $passwd Userpassword md5 hash!
     * @return 0 login failed, 1 login OK, -1 abgelaufen
     */
    public function Login($user_name, $passwd) {
        $jss = file_get_contents($this->_user_data_file);
        if ($jss == '') {
            return 0;
        } else {
            $users = json_decode(stripslashes($jss), TRUE);
            foreach ($users as $udata) {
                if (strcmp($udata['user_name'], $user_name) == 0) {
                    if (strcmp($udata['password_hash'], $passwd) == 0) {
                        if (intval($udata['ablaufdatum']) > time() || strcmp($udata['ablaufdatum'], "") == 0 || $udata['ablaufdatum'] == 0) {
                            //temporaer? Einige fehlerhafte eintraege reparieren..
                            if ($udata['rolle']==""){
                                $this->UpdateUser($user_name, Array("rolle" => "100"));
                            }
                            //---------
                            return 1;
                        } else { //faster
                            return -1;//abgelaufen
                        }
                    }
                }
            }
            return 0;
        }
    }

    /**
     * Updates information about user $user_name
     * @param string $user_name Username
     * @param mixed $arg2 Can be array of user settings.<br/>
     * Or password. If password then sets $new_permission as user permissions parameter
     * @param string $new_permission [Optional = null] new user permissions. Needed only if $arg2 is string password
     * @return boolean FALSE if not user found, otherwise TRUE
     */
    public function UpdateUser($user_name, $arg2, $new_permission = null) {
        $jss = file_get_contents($this->_user_data_file);
        if ($jss == '') {
            return FALSE;
        } else {

            $users = json_decode(stripslashes($jss), TRUE);
            for ($i = 0; $i < count($users); $i++) {
                //overloading arg1 = username, arg2 = array
                if (is_array(func_get_arg(1)) && func_num_args() == 2) {
                    if (strcmp($users[$i]['user_name'], $user_name) == 0) {
                        $this->fillByArray($users[$i], $arg2);
                        break;
                    }
                // arg1 = user_name, arg2 pw, fuer aeltere funcs?
                } elseif (strcmp($users[$i]['user_name'], $user_name) == 0) {
                    $new_pwd = $arg2;
                    if ($new_pwd != '') {
                        $users[$i]['password_hash'] = $new_pwd;
                    }
                    $users[$i]['permission'] = $new_permission;
                    break;
                }
            }
            $jss1 = json_encode($users);
            file_put_contents($this->_user_data_file, $jss1);
            return TRUE;
        }
    }

    /**
     * Remove user $user_name
     * @param string $user_name
     * @return boolean Success
     */
    public function RemoveUser($user_name) {
        $jss = file_get_contents($this->_user_data_file);
        if ($jss == '') {
            return FALSE;
        } else {
            $users = json_decode(stripslashes($jss), TRUE);
            for ($i = 0; $i < count($users); $i++) {
                if (strcmp($users[$i]['user_name'], $user_name) == 0) {
                    array_splice($users, $i, 1);
                    break;
                }
            }
            $jss1 = json_encode($users);
            file_put_contents($this->_user_data_file, $jss1);
            return TRUE;
        }
    }

    /**
     * Test if user $user_name has permission to path $file_path
     * @param string $user_name Username
     * @param string $file_path Filepath
     * @return boolean TRUE if has permission
     */
    public function UserHasPermission($user_name, $file_path) {
        $user_data_string = file_get_contents($this->_user_data_file);
        if ($user_data_string == '') {
            return FALSE;
        }
        $user_data = json_decode(stripslashes($user_data_string), TRUE);
        foreach ($user_data as $ud) {
            if (strcmp($ud['user_name'], $user_name) == 0) {
                if ($ud['rolle'] == "1000") {
                    return TRUE;
                }
                return $this->checkPermission($file_path, $ud['permission']);
            }
        }
        return FALSE; // no such user!
    }

    /**
     * Tests if $file_path is in $permissions
     * @param string $file_path filepath to test
     * @param string $permissions string with permissions
     * @return boolean True if in string
     */
    private function checkPermission($file_path, $permissions) {
        if ($permissions == '/') { // can do everything
            return TRUE;
        } else {
            $permissions = explode('|', $permissions);
            foreach ($permissions as $perm) {
                if (substr($perm, -1) == '/') { // has permission of a dir
                    $test_perm = $perm;
                    if (strpos($file_path, $test_perm) !== FALSE) {
                        return TRUE;
                    } else {
                        continue;
                    }
                } else { // has permission only of a certain file
                    if ($perm == $file_path) {
                        return TRUE;
                    } else {
                        continue;
                    }
                }
            }
            return FALSE;
        }
    }

    public function ChangePermissionPath($user_name, $old_path, $new_path) {
        // ...
    }

    /**
     * Get user permission string
     * @param string $user_name Username
     * @return string User permissions
     */
    public function GetPermission($user_name) {
//        $user_data_str = file_get_contents($this->_user_data_file);
//        $user_data = json_decode(stripslashes($user_data_str), TRUE);
//        foreach ($user_data as $ud) {
//            if ($ud['user_name'] == $user_name) {
//                return $ud['permission'];
//            }
//        }
        $user = $this->GetUser($user_name, "array");
        if ($user != null) {
            if ($user['rolle']=="1000") {
                return "/";
            }
            return $user['permission'];
        } else {
            return 'NO_SUCH_USER';
        }
    }


    /**
     * pruefen ob dem benuzer $user erlaubt ist die $menuId von $menu mit vordefinierten $rollen zu zugreifen
     *
     * @param int $menuId <p>
     * menu id to check
     * </p>
     * @param string $user <p>
     * user name to check
     * </p>
     * @param string $menu_value_to_check <p>
     * what menue value (of array) will be checked, id? link?
     * </p>
     * @param array $menu <p>
     * menu values with id, role reqiurement, name etc..
     * </p>
     * @param array $rollen <p>
     * User roles values(e.g 'Admin' => 1000)
     * </p>
     * @return bool allowed or not
     */
    public function isAllowAccess($menuId, $user, $menu_value_to_check = "id", $menu=null, $rollen=null ) {

        //fast check if admin..
        if(strcmp($user, NavTools::getServerAdmin())==0){
            return true;
        }

        //falls keine menu oder rollen übergeben wurde, dann die aus dem config.php holen. <require config.php !>
        if($menu == null){$menu = $GLOBALS["ne2_menu"];}
        if($rollen == null){$rollen = $GLOBALS["ne2_user_roles"];}
        if($menu != null && $rollen != null && $menuId != null){ // die werte muessen angegeben werden
            foreach ($menu as $value) {
                if ($value[$menu_value_to_check] == $menuId) {
                    $need_role = $value['role'];
                    break;
                }
            }

            if ($need_role == null){//falls nichts gefunden
                return false;
            }elseif ($need_role == "public") { //falls public immer erlauben
                return true;
            }elseif($user == null){ //falls kein user und kein public, nicht erlauben
                return false;
            }else{
                $need_value = $rollen[$need_role]['value'];
            }
            $user_data = $this->getUser($user, 'array');
            if($user_data == null) return false;

            if ($user_data['rolle'] >= $need_value) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }


    /**
     * Tests if current user has minimal access level $access
     *
     * @global array $ne2_user_roles
     * @global string $g_current_user_name
     * @param mixed $access<p>
     * minimal rights level needed to have access<br>
     * can be role name (String), or role level (numeric)<br>
     * in case $access is wrong argument, false will be returned
     * </p>
     * @return boolean
     */
    public function hasAccesLevel($access, $userName = null) {
        global $ne2_user_roles, $g_current_user_name;

        if (is_null($userName)) {
            if (!is_null($g_current_user_name)) {
                $userName = $g_current_user_name;
            } else {
                return false;
            }
        }

        if (!is_numeric($access)) {
            $needAccessLvl = NavTools::ifsetor($ne2_user_roles[$access]['value']);
        } else {
            $needAccessLvl = $access;
        }

        //if nothing found return false
        if (is_null($needAccessLvl) || !is_numeric($needAccessLvl)) {
            return false;
        }

        $um = new UserMgmt();
        $user_data = $um->GetUser($userName, 'array');
        if ($user_data['rolle'] >= $needAccessLvl) {
            return true;
        } else {
            return false;
        }

    }


    /**
     * Test if user has acces to the php file
     * Requires config.php for $ne2_default_file_rights, $ne2_config_info
     * @uses auth.php for $g_current_user_name
     *
     * @global array $ne2_default_file_rights
     * @global string $g_current_user_name
     * @global array $ne2_config_info
     *
     * @param string $phpPath Full or relative to NavEditor/ path to the php file
     * @param string $userName [Optional] Username to test. If null try to get global set variable $g_current_user_name
     * @return boolean true if access allowed, false otherwise
     */
    public function isAllowAccesPHP($phpPath, $userName = null){
        //TODO ausnahmen bei jedem User, pruefen.
        global $ne2_default_file_rights, $g_current_user_name, $ne2_config_info;
        if(is_null($userName)){
            $userName = $g_current_user_name;
            if(is_null($g_current_user_name)){
                return false;
            }
        }

        //cut root path if found
        $phpPath = str_replace($ne2_config_info['app_path_without_host'], '', $phpPath);

        $needRole = NavTools::ifsetor($ne2_default_file_rights[$phpPath]);
        if (!is_null($needRole)) {
            if(!$this->hasAccesLevel($needRole, $userName)){
                return false;
            }
        }

        return true;
    }


    /**
     * Log login time
     * @param string $user Username
     */
    public function saveLoginTime($user) {
        if (isset($user)) {
            $this->UpdateUser($user, Array('letzter_login' => time()));
        }
    }


    private function fillByArray(&$arrayOriginal, $arrayInfo) {
        foreach ($arrayOriginal as $key => $value) {
            if (isset($arrayInfo[$key])) {
                $arrayOriginal[$key] = $arrayInfo[$key];
            }
        }
        return $arrayOriginal;
    }

    /**
     * test if user data is in old format, and changes to new if needed
     */
    private function checkForNewFormat() {
        if (!file_exists($this->_user_data_file)) {
            touch($this->_user_data_file); // create file if not exists
        } else {
            $jss = file_get_contents($this->_user_data_file);
            $test_jss = json_decode(stripslashes($jss), TRUE);
            if (!is_null($test_jss[0])) {
                //test only first user (admin) -> checkAll ? +stability, -performance
                if ($this->arrayDiff($this->_user_default_array, $test_jss[0])) {
                    $new_jss = $this->modifyToNewFormat($test_jss);
                    file_put_contents($this->_user_data_file, $new_jss) OR die('Could not write to file: ' + $this->_user_data_file); // rewrite!
                }
            }
        }
    }

    /**
     * Modify user settings to new format
     * @param array $paramsAll new parameters
     * @return string jss encoded new parameters
     */
    private function modifyToNewFormat($paramsAll) {
        if (!isset($paramsAll) || $paramsAll == null) {
            return null;
        }
        $copy_jss = array();
        foreach ($paramsAll as $params) {
            if (!$this->arrayDiff($this->_user_default_array, $params)) { //check if array keys are different
                $retVal = $params;
            } else {
                //only for update from old version where /index.shtml means directory acces
                if (!$this->arrayDiff($params, Array("user_name"=>Array(),"password_hash"=>Array(),"permission"=>Array()))){
                    $params['permission'] = $this->modifyPermissions($params['permission']);
                    //set rolle and mail for admin
                    if($params["user_name"] == NavTools::getServerAdmin()){
                        $params["rolle"] ="1000";
                        $params["email"] = $_SERVER['SERVER_ADMIN'];
                    }else{
                    //set other users as just "user"
                        $params["rolle"] ="100";
                    }
                }

                $arrayTemplate = $this->_user_default_array;
                //copy all possible from old array
                $this->fillByArray($arrayTemplate, $params);
                // add full permission, in case it is missing
                if (!isset($params['permission'])) {
                    $arrayTemplate['permission'] = '/';
                }


                $retVal = $arrayTemplate;
            }
            array_push($copy_jss, $retVal);
        }
        $new_jss = json_encode($copy_jss);
        return $new_jss;
    }


    /**
     * macht einige tests fuer die Benutzerdaten, und repariert was sicher fehlerhaft ist
     * @param String $userName <p>
     * Benutzername
     * </p>
     * @todo create it! :)
     */
    private function repairUser($userName){
        //TODO
    }


    //modify permissions to new format ('/index.shtml' => '/')
    private function modifyPermissions($permissions){
        if(!is_null($permissions)){
            return str_replace("/index.shtml", "/", $permissions);
        }
    }

    private function arrayDiff($array1, $array2) {
        if (count(array_diff_assoc(array_keys($array1), array_keys($array2))) == 0 && count($array1) == count($array2)) {
            return false;
        } else {
            return true;
        }
    }

}

?>
