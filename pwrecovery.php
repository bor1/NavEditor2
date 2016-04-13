<?php
require_once('app/config.php');
require_once('app/log_funcs.php');
session_start();
$oper = "pwvergessen"; //mit der operation anfangen
$keyfilepath = $ne2_config_info['temp_path'].'.key_tmp';
$logCounter = array();
$toWait = 0;

//code eingeben -> mail send.
if(isset($_POST['btnReset'])) {
	$logCounter = countLog('mailSent');
	$toWait = calcRestZeit($logCounter['counter'], $logCounter['lasttry']);
	if ($logCounter['counter'] > 0 && $toWait> 5){
		$oper = "wait";
	} else {
		$current_user_name = $_POST['txtUserName'];
		$current_zahl= $_POST['txtCheck'];
                require_once('app/classes/UserMgmt_Class.php');
		$um = new UserMgmt();
                $userArray = $um-> GetUser($current_user_name, 'just as array');
		$_SESSION['userName'] = $current_user_name;
		if(!isset($_SESSION['rndZahl'])){
			echo "Bitte Cookies aktivieren!";
		}elseif(!is_null ($userArray) && strcmp($current_zahl, $_SESSION['rndZahl']) == 0){
			$oper = "mailsend";
                        //bei dem Server-Admin nur auf server mail versenden
			$user_mail = ($current_user_name == NavTools::getServerAdmin()) ? $_SERVER['SERVER_ADMIN'] : $userArray['email'];
                        if($user_mail == ""){
                            $oper = "nomail";
                            logadd("nomail");
                        }else{
                            $key = (string)(rand(0,1000000)) + $user_mail;
                            $key = md5($key);
                            $md5key = md5($key);

                            $headers .= "MIME-Version: 1.0\r\n";
                            $headers .= "Content-type: text/plain; charset=utf-8\r\n";
                            $text = "Hallo $current_user_name,\r\n";
                            $text .= "Sie erhalten diese E-Mail, weil Sie ein neues Passwort angefordert haben.\n\n";
                            $text .= "Um das neue Passwort zu setzen, benutzen Sie bitte innerhalb der n\xC3\xA4chsten 24 Stunden den folgenden Link: http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?key=$key&user=$current_user_name\n\n";
                            $text .= "Mit freundlichen Gr\xC3\xBC\xC3\x9Fen\n\n";
                            $text .= 'Das RRZE Team.';

                            if(!mail($user_mail, 'Passwort wiederherstellen', $text, $headers)){
                                $oper = "nomail";
                                logadd("nomail");
                            }else{
                                $fh = fopen($keyfilepath."-".$current_user_name, 'w') or die('Cannot create file!');
                                fwrite($fh, $md5key);
                                fclose($fh);

                                logadd("mailSent");
                            }
                       }
		} else {
			$oper = "wrongnum";
		}
	}
// link von dem Mail benutzt
}elseif(isset($_GET['key']) && isset($_GET['user'])) {
	//$_SESSION['key'] = $_GET['key'];
        $keyfilepath .= "-".$_GET['user'];
	$logCounter = countLog('linkUsed');
	//20 Versuche werden toleriert.
	$toWait = calcRestZeit($logCounter['counter']-20, $logCounter['lasttry']);
	if ($logCounter['counter'] > 0 && $toWait> 5){
		$oper = "wait";
	}elseif(file_exists($keyfilepath)){
		if((time()-filectime($keyfilepath)) < (24*60*60)){
			$key = file_get_contents($keyfilepath);
			if (strcmp($key, md5($_GET['key'])) == 0){
				$oper = "resetpw";
				logadd("linkUsed");
			} else {
				$oper = "wrongkey";
			}
		} else{
			$oper = "wrongkey";
		}
	}else {
		$oper = "wrongkey";

	}
	//form fuer pw aendern benutzt
} elseif(isset($_POST['btnPwSet'])) {
	$logCounter = countLog('wrongkey');
	//20 Versuche werden toleriert.
	$toWait = calcRestZeit($logCounter['counter']-20, $logCounter['lasttry']);
	//keine fehlermeldung, da sowas nur bei hackversuchen vorkommen kann
	sleep((int)$toWait);
	$pw1 = $_POST['txtPw'];
	$pw2 = $_POST['txtPwRe'];
	$key = $_POST['key'];
        $user = $_POST['user'];
        $keyfilepath .= "-".$user;
	//$key = $_SESSION['key'];

	if(strcmp($pw1, $pw2) == 0 && $pw1 != ""){
		if(file_exists($keyfilepath)){
			$keyfile = file_get_contents($keyfilepath);
			if (strcmp(md5($key), $keyfile) == 0){
				require_once('app/classes/UserMgmt_Class.php');
				$um = new UserMgmt();
				$um->UpdateUser($user, Array('password_hash' => md5($pw1)));
				unlink($keyfilepath);
				//session beenden
				session_unset();
				$_SESSION=array();
				$oper = "changed";
				logadd("pwChanged");
			}else{
				echo 'Key ist falsch? Oo Bitte nicht hacken!';
				logadd('wrongkey');
			}
		} else {
			echo 'Key File existiert nicht mehr';
			logadd('nokey');
		}
	}else{
		echo 'Passwort stimmt nicht';
	}
}
$_SESSION['rndZahl'] = (string)(rand(10000,99999));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Passwort vergessen - <?php echo($ne2_config_info['app_titleplain']); ?></title>
<link rel="stylesheet" type="text/css" href="css/styles.css?<?php echo date('Ymdis'); ?>" />
</head>
<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
<script type="text/javascript">
var toWaitJs = <?php echo $toWait ?>;
var restZeit = new Date(toWaitJs*1000);


function startTime(){
	var h=Math.floor(restZeit.getTime()/(60*60*1000));
	var m=restZeit.getUTCMinutes();
	var s=restZeit.getUTCSeconds();
	// add a zero in front of numbers<10
	h=checkTime(h);
	m=checkTime(m);
	s=checkTime(s);
	if(restZeit > 0 ){
		$('#timeBlock').html(h+':'+m+':'+s);
		restZeit.setTime(restZeit.getTime()-1000);
		t=setTimeout('startTime()',999);
	} else {
		window.location.href = 'login.php';
	}
}

function checkTime(i){
if(i==0){
i = "00";
}else if (i<10){
  i="0" + i;
}
return i;
}



$(document).ready(function() {
	if(toWaitJs > 5 ){
		startTime();
	}
	$("#frmPw").submit(function(event) {
		if ($("#txtPw").val() != $("#txtPwRe").val()){
			event.preventDefault();
			alert("Passwort stimmt nicht, bitte noch mal eingeben");
			$('#frmPw')[0].reset();
			//$("#txtPw").val("");
			//$("#txtPwRe").val("");
		}
	});
});

</script>
<body>
<div id="wrapper">
	<h1 id="header">Passwort vergessen</h1>
	<?php
	if (strcmp($oper, "mailsend") == 0){
		echo "<p style='text-align:center;font-size:large;'>Mail mit dem Aktivierungslink wurde versendet</p>";
	}
	elseif (strcmp($oper, "wrongnum") == 0 || strcmp($oper, "pwvergessen") == 0){
	?>
		<div id="contentPanel1">
			<form id="frmLogin" name="frmLogin" action="pwrecovery.php" method="post" style="width: 100%;text-align: center;"><br><br>
				<label for="txtUserName" style="width:16em;display:inline-block;">Benutzerkennung:</label>
				<input type="text" id="txtUserName" name="txtUserName" size="16" class="textBox" /><br><br>
				<label for="txtCheck" style="width:16em;display:inline-block;">Bitte die Zahl eingeben: <b><?php echo $_SESSION['rndZahl']?></b></label>
				<input type="text" id="txtCheck" name="txtCheck" size="16" class="textBox" /><br><br>
				<input type="submit" id="btnReset" name="btnReset" class="button" value="Passwort Zur&uuml;cksetzen" />
			</form>
		</div>
	<?php
	}
	if (strcmp($oper, "wrongnum") == 0){
		echo "<script type='text/javascript'>alert('Die Zahl oder der Loginname ist falsch, bitte versuchen Sie noch ein mal.');</script>";
	} elseif(strcmp($oper, "nomail") == 0){
                echo "<script type='text/javascript'>alert('Mail bei dem User nicht gefunden');</script>";
        }
	elseif (strcmp($oper, "resetpw") == 0){?>
		<div id="contentPanel1">
			<form id="frmPw" name="frmPw" action="pwrecovery.php" method="post" style="width: 100%;text-align: center;"><br><br>
				<label for="txtPw" style="width:20em;display:inline-block;">Neues Passwort:</label>
				<input type="password" id="txtPw" name="txtPw" size="16" class="textBox" /><br><br>
				<label for="txtPwRe"style="width:20em;display:inline-block;">Best&auml;tigung des Passworts:</label>
				<input type="password" id="txtPwRe" name="txtPwRe" size="16" class="textBox" /><br><br>
				<input type="submit" id="btnPwSet" name="btnPwSet" class="button" value="Passwort speichern" />
				<input type="hidden" id="key" name="key" value="<?php echo $_GET['key']?>">
                                <input type="hidden" id="user" name="user" value="<?php echo $_GET['user']?>">
			</form>
		</div>
	<?php
	}
	elseif (strcmp($oper, "wrongkey") == 0){
		echo "<p style='color:red;text-align:center;'>Das Link ist veraltet oder existiert nicht<br>\n";
		echo "<a href='login.php'>Zur&uuml;ck zur Login</a><p>";
	}
	elseif (strcmp($oper, "wait") == 0){
		echo "<p style='text-align:center;font-size:large;'>Zu viele Versuche, bitte warten Sie noch <div id='timeBlock' name='timeBlock' style='color: red;text-align:center;font-size:x-large;'></div><p><br>\n";
	}elseif (strcmp($oper, "changed") == 0){
		echo "<script type='text/javascript'>alert(unescape('Passwort erfolgreich ge%E4ndert%21'));";
		echo "window.location.href = 'login.php';</script>";
		//echo "<br><br><p>Passwort erfolgreich ge&auml;ndert!</p>";
		//echo "<p>Klicken Sie bitte auf <a href='login.php'>diesen Link</a> um sich anzumelden.</p>";
	}
 	?>

	<?php require('common_footer.php'); ?>
</div>
</body>

</html>
