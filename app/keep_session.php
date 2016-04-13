<?php
require_once('../auth.php');

if( !ini_get('safe_mode') ){
            set_time_limit(10);
        } 
ini_set("max_input_time", 2);         
        
if (isset($_COOKIE['keep_session_counter'])) {
	$keep_session_counter = $_COOKIE['keep_session_counter'];
	$keep_session_counter++;; 
}  else {
	$keep_session_counter = 1;
}
setcookie('keep_session_counter', $keep_session_counter, time() + $ne2_config_info['session_timeout']  );		

?>