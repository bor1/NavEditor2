<?php
/**
 * php file to handle ajax requests, to manage Users.
 * ajax from user_manager.php
 */


require_once('config.php');
require_once('../auth.php');
require_once('classes/UserMgmt_Class.php');
require_once ('classes/Input.php');



$oper = $_REQUEST['json_oper'];
$um = new UserMgmt();

if($oper == 'get_users') {
    $users = $um->GetUsers('array');
            for($i = 0; $i< count($users); $i++){
                $users[$i]['password_hash'] = "";
            }
            echo(json_encode($users));
}

if($oper == 'create_user') {
    $new_user_name = NavTools::filterSymbols(Input::get_post('user'));//nicht erlaubte symbole filtern
    $params = json_decode(stripslashes(Input::get_post('params')), true);
    if(!$um->AddUser($new_user_name, $params)) {
        echo('Add user failed, maybe the username already exists!');
    } else {
        echo('Add user ' . $new_user_name . ' done!');
    }
}

if($oper == 'update_user') {
            $user = Input::get_post('user');
            $paramArray = json_decode(stripslashes(Input::get_post('params')), true);
            $paramArray['user_name'] = NavTools::filterSymbols($paramArray['user_name']); //nicht erlaubte symbole in user_name filtern
            //remove pw from params if not set, or hash of empty string ""
            if(!isset($paramArray['password_hash']) || $paramArray['password_hash'] == "d41d8cd98f00b204e9800998ecf8427e"){
                unset($paramArray['password_hash']);
            }
            //remove not editable values
            $userArray = array_diff_key($paramArray, get_ne2_user_params_not_editable());
            if(!$um->UpdateUser($user, $userArray)) {
        echo('Update user failed!');
    } else {
        echo('Update user ' . $user . ' done!');
    }
}

if($oper == 'remove_user') {
    $user_name = Input::get_post('user_name');
            if(NavTools::getServerAdmin() == $user_name){
                echo('Can not remove admin!');
                return;
            }
    if(!$um->RemoveUser($user_name)) {
        echo('Remove user failed!');
    } else {
        echo('Remove user OK!');
    }
}


?>
