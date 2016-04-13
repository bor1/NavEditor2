<?php

/**
 * Authentification
 * requires config.php, UserMgmt_Class.php, log_funcs.php, NavTools.php
 *
 * @TODO save user to $_SESSION by login, if saved, ignore login routine by auth.php.
 */

namespace auth {
    require_once('app/config.php');
    require_once('app/classes/UserMgmt_Class.php');
    require_once('app/log_funcs.php');

    /**
     * Call if login failed
     */
    function login_failed() {
        goToLogin();
        logadd('loginFail');
        exit;
    }


    /**
     * Go to login page.
     * @global array $ne2_config_info
     */
    function goToLogin() {
        global $ne2_config_info;
        $host = $_SERVER['HTTP_HOST'] . $ne2_config_info['app_path_without_host'];
        header('Location: http://' . $host . 'login.php');
    }

    /**
     * Check if file is for public access
     * @global array $ne2_config_info
     * @return boolean TRUE if public file
     */
    function checkPublic() {
        global $ne2_config_info;
        $uri = $_SERVER['REQUEST_URI'];
        $appuri = $ne2_config_info['app_path_without_host'];
        $allowthis = FALSE;
        for ($i = 0; $i < count($ne2_config_info['nologin_file']); $i++) {
            $thisuri = $appuri . $ne2_config_info['nologin_file'][$i];
            if (strcmp($uri, $thisuri) == 0) {
                $allowthis = TRUE;
                break;
            }
        }
        return $allowthis;
    }


    //global variables
    global $ne2_config_info;
    global $g_current_user_permission;
    global $g_current_user_name;
    global $is_admin;

    ini_set("session.use_only_cookies", "on");
    session_set_cookie_params($ne2_config_info['session_timeout']); // don't know if works...
    session_start();

    $um = new \UserMgmt();

    //in case there are no users, have to activate, go to aktivierung.php
    if (is_null($um->GetUsers())) {
        header("Location: http://" . $_SERVER['HTTP_HOST'] . $ne2_config_info['app_path_without_host'] . "aktivierung.php");

        //otherwise login process
    } else {

        $is_admin = FALSE;
        $current_user_name = NULL;
        $current_user_pwd = NULL;

        $has_session = FALSE;
        $has_cookie = FALSE;

        $loginResult = 'FAIL'; //values: FAIL, OK, WAIT
        //check if session or cookie is activated
        if (isset($_SESSION['ne2_username'])) {
            $has_session = TRUE;
        } elseif (isset($_COOKIE['ne2_username'])) {
            $has_cookie = TRUE;
        }

        //if cookie or session is activated
        if ($has_session || $has_cookie) {
            //waitTimeForLogin ist eine aufwaendige function, dafuer aber mehr sicherheit,
            //da man theoretisch die session/cookie gesp. passwoerter immer ersetzen kann.
            //Ob es sich lohnt..
            $toWait = waitTimeForLogin();
            if ($toWait > 5) {
//                goToLogin();
                //need to wait, too many login tries
                $loginResult = 'WAIT';

                //dont need to wait, can try login
            } else {

                //get username and password
                if ($has_session) {
                    $current_user_name = $_SESSION['ne2_username'];
                    $current_user_pwd = $_SESSION['ne2_password'];
                } else { //$has_cookie
                    $current_user_name = $_COOKIE['ne2_username'];
                    $current_user_pwd = $_COOKIE['ne2_password'];
                }

                //try to login
                if ($um->Login($current_user_name, $current_user_pwd) != 1) { // login failed
//                    login_failed();
                    $loginResult = 'FAIL';
                } else { // login ok
                    $loginResult = 'OK';

                    //routine for logged in user

                    $g_current_user_permission = $um->GetPermission($current_user_name);
                    $g_current_user_name = $current_user_name;

                    //set $is_admin, fallback. TODO remove. replace with permissions check
                    if (strcmp($current_user_name, \NavTools::getServerAdmin()) == 0) {
                        $is_admin = TRUE;
                    }

                    //set new cookie
                    setcookie('ne2_username', $current_user_name, time() + $ne2_config_info['session_timeout']);
                    setcookie('ne2_password', $current_user_pwd, time() + $ne2_config_info['session_timeout']);

                    //add log for keep_session.php //TODO remove, no need?
                    $keep_session_prog = $ne2_config_info['app_path_without_host'] . "app/keep_session.php";
                    if (($_SERVER['PHP_SELF'] == $keep_session_prog)) {
                        $message = ($has_session) ? '(session)' : '(cookie)';
                        $message .= " user: " . $current_user_name;
                        if (isset($_COOKIE['keep_session_counter'])) {
                            $message .= " count: " . $_COOKIE['keep_session_counter'];
                        }
                        logadd('sessionup', $message);
                    }
                }
            }
        }




        //test file access
        //if not public... otherwise OK, nothing to do
        if (!checkPublic()) {

            //switch loginResult
            switch (strtoupper($loginResult)) {
                case 'OK'://if logged in
                    //test access for the requested file
                    $requested_file_path = str_replace($ne2_config_info['app_path_without_host'], "", $_SERVER["SCRIPT_NAME"]);
                    if (!$um->isAllowAccesPHP($requested_file_path, $current_user_name)) {
                        auth401('You dont have permission for this file');
                    }
                    break;
                case 'WAIT': //if have to wait
                    goToLogin();
                    break;

                case 'FAIL': //if failed to login
                    login_failed();
                    break;
                default:
                    throw new \Exception('Cannot login...');
                    break;
            }
        }
    }
}


//global usefull

namespace {

    function auth401($msg = 'Contact SERVER_ADMIN for your account information.') {
        header('WWW-Authenticate: Basic realm="NavEditor2"');
        header('HTTP/1.0 401 Unauthorized');
        $backlink = '<A HREF="javascript:javascript:history.go(-1)">Click here to go back to previous page</A>';
        echo $msg, '<br />', $backlink;
        exit;
//        \auth\login_failed();
    }

}
?>
