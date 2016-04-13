<?php
require_once('app/config.php');
require_once('app/log_funcs.php');
require_once('app/classes/UserMgmt_Class.php');

$toWait = 0;
$keyfilepath = $ne2_config_info['temp_path'].'.akt_key_tmp';

function keyvergleichen($keyInput){
	global $keyfilepath;
	$retvalue = false;
	if(file_exists($keyfilepath)){
		if((time()-filectime($keyfilepath)) < (24*3600)){
			$key = file_get_contents($keyfilepath);
			if (strcmp($key, md5($keyInput)) == 0){
				$retvalue = true;
			}
		}
	}
	return $retvalue;
}
function keyerstellen($md5key){
	global $keyfilepath;
	$fh = fopen($keyfilepath, 'w') or die('Cannot create file!');
	fwrite($fh, $md5key);
	fclose($fh);
}

function calcWaitTimeByOper($operation){
	$logCounter = countLog($operation);
	$toWait = calcRestZeit($logCounter['counter'], $logCounter['lasttry']);
	return $toWait;
}

function sendmail($admin_mail, $key){
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/plain; charset=utf-8\r\n";
	//mail inhalt beginn
	$server_admin = NavTools::getServerAdmin();
	$text = "Hallo ".$server_admin."\n";
	$text .= "Sie erhalten diese E-Mail, da Sie zur Aktivierung des CMS NavEditor\n";
	$text .= "f\xC3\xBCr den Webauftritt ".$_SERVER['SERVER_NAME']."\n";
	$text .= "ein Aktivierungsschl\xC3\xBCssel angefordert haben.\n\n";

	$text .= "Um den NavEditor zu aktivieren und ein Passwort zur weiteren\n";
	$text .= "Benutzung festzulegen, klicken Sie bitte auf folgenden Link:\n";
	$text .= "	http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?oper=uselink&key=".$key."\n\n";

	$text .= "oder geben auf der Seite\n";
	$text .= "	http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?oper=keyeingabe\n";
	$text .= "den Aktivierungsschl\xC3\xBCssel\n";
	$text .= "	".$key."\n";
	$text .= "ein.\n";
	$text .= "Mit freundlichen Gr\xC3\xBC\xC3\x9Fen\n\n";
	$text .= '	Das RRZE Team.';
	//mail inhalt ende
	mail($admin_mail, 'NavEditor aktivieren', $text, $headers);
}

// wenn die seite unnoetig aufgerufen wird, zum login weiterleiten.
$um = new UserMgmt();
if(!is_null($um->GetUsers())) {
	header("Location: login.php");
}


if (isset($_GET['oper'])){
	$oper = $_GET['oper'];
}else{
	$oper = "aktivierung"; //mit der operation anfangen
}

if ($oper == "sendmail"){
	$toWait = calcWaitTimeByOper('mailSent');
	if ($toWait > 5){
		$oper = "wait";
		$message = "Mail zu oft angefordert, bitte warten Sie noch: ";
	}else{
		$admin_mail = $_SERVER['SERVER_ADMIN'];
		$key = (string)(rand(0,1000000)) . $admin_mail;
		$key = md5($key);
		$md5key = md5($key);
		keyerstellen($md5key);
		sendmail($admin_mail, $key);
		logadd("mailSent");
		$oper = "keyeingabe";
	}
}else if($oper == "uselink"){
	$toWait = calcWaitTimeByOper('wrongKey');
	if ($toWait > 5){
		$oper = "wait";
		$message = "Zu viele Fehlversuche den Schl&uuml;ssel einzugeben, bitte warten Sie noch: ";
	}else{
		$key = $_GET['key'];
		logadd("aktLinkUsed"); //probably dont need
		if(!keyvergleichen($key)){
			$oper = "wrongkey";
			$message = "Der Schl&uuml;ssel ist falsch";
			logadd("wrongKey");
			unlink($keyfilepath);
		}
	}
}else if($oper == "aktiviert"){
	$toWait = calcWaitTimeByOper('wrongKey');
	if ($toWait > 5){
		$oper = "wait";
		$message = "Zu viele Fehlversuche den Schl&uuml;ssel einzugeben, bitte warten Sie noch: ";
	}else{
		$key = $_POST['key'];
		$server_admin = NavTools::getServerAdmin();
		$user_pwd = $_POST['txtPw'];
		if(keyvergleichen($key)){
			if($um->AddUser($server_admin, Array('password_hash' => md5($user_pwd)), '/')){
				$message = "Webauftritt erfolgreich aktiviert!";
			}else{
				$message = "Ein Fehler aufgetretten";
			}
		}else{
			$message = "Key wrong!? Oo pls dont hack!";
			logadd("wrongKey");
		}
		unlink($keyfilepath);
	}

}









?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Aktivierung - <?php echo($ne2_config_info['app_titleplain']); ?></title>
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
		window.location.href = '<?php echo basename(__FILE__); ?>';
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
		}
	});
	$("#getActKey").click(function(){
		window.location.href = 'aktivierung.php?oper=sendmail';
	});

	$("#frmKey").submit(function(event) {
		var keyvalue = $("#key").val();
		keyvalue = keyvalue.toLowerCase();
		$("#key").val($.trim(keyvalue));
		if ($("#key").val().length != 32){
			event.preventDefault();
			alert(unescape('Der Schl%FCssel muss 32 zeichen lang sein%21'));
		}
	});

});

</script>
<body>
<div id="wrapper">
	<h1 id="header">Aktivierung</h1>
        <div id="textbereich" style="text-align: center">
	<?php
	if (strcmp($oper, "aktivierung") == 0){?>
		<p>Der NavEditor wurde noch nicht aktiviert und kann daher noch nicht benutzt werden.<br>
            Zur Aktivierung fordern Sie durch Klicken auf den folgenden Button ein Aktivierungsschl&uuml;ssel
            an:</p>
		<input type="button" id="getActKey" name="getActKey" class="button" value=" Aktivierungsschl&uuml;ssel anfordern" /></center><br>
        <p>Dieser Schl&uuml;ssel wird &uuml;ber E-Mail an die Adresse des f&uuml;r den Webauftritt eingetragenen Hauptwebmasters geschickt.<br>
Nach Zustellung des Aktivierungsschl&uuml;ssel geben Sie diesen auf der folgenden Seite ein oder klicken einfach auf den Link in der E-Mail.<br>
Danach werden Sie aufgefordert ein neues Passwort zu vergeben, mit dem Sie sich zuk&uuml;nftig im NavEditor authentifizieren.
		</p>
                <p>
                    <b>
                        Hinweis:
                    </b>
                    Sollten Sie als Hauptwebmaster innerhalb der nächsten Minuten keine E-Mail erhalten,<br/>
                    liegt m&ouml;glicherweise eine falsche Zuordnung der Webmasterkennung zu der E-Mailadresse
                    vor.<br> Wenden Sie sich in diesem Fall an <a href="mailto:webmaster@rrze.uni-erlangen.de">webmaster@rrze.fau.de</a>.

                </p>

	<?php
	}
	//code anfordern geklickt
	elseif (strcmp($oper, "keyeingabe") == 0){?>
		<p>Geben Sie hier den Aktivierungsschl&uuml;ssel ein, den Sie via E-Mail erhalten haben:</p>
		<form id="frmKey" name="frmKey" action="aktivierung.php" method="get" style="width: 100%;text-align: center;">
			<input type="text" id="key" name="key" size="37" class="textBox" /><br>
			<input type="submit" id="weiter" name="weiter" class="button" value="weiter" />
			<input type="hidden" id="oper" name="oper" value="uselink">
		</form>
	<?php
	}
	// link benutzt oder code eingegeben
	elseif (strcmp($oper, "uselink") == 0){?>

			<form id="frmPw" name="frmPw" action="aktivierung.php?oper=aktiviert" method="post" style="width: 100%;text-align: left;margin-left:100px;"><br><br>
				<label for="txtPw" style="width:20em;display:inline-block;">Neues Passwort:</label>
				<input type="password" id="txtPw" name="txtPw" size="16" class="textBox" /><br><br>
				<label for="txtPwRe"style="width:20em;display:inline-block;">Best&auml;tigung des Passworts:</label>
				<input type="password" id="txtPwRe" name="txtPwRe" size="16" class="textBox" /><br><br>
				<input type="submit" id="btnPwSet" name="btnPwSet" class="button" value="aktivieren" />
				<input type="hidden" id="key" name="key" value="<?php echo $_GET['key']?>">
			</form>

	<?php
	}
	// code war falsch
	elseif (strcmp($oper, "wrongkey") == 0){
		echo "<p style='color:red;text-align:center;'>".$message."<br>\n";
		echo "<a href='aktivierung.php?oper=sendmail'>Noch mal den Schl&uuml;ssel senden</a><p>";
	}
	elseif (strcmp($oper, "wait") == 0){
		echo "<p style='text-align:center;font-size:large;'>".$message."<div id='timeBlock' name='timeBlock' style='color: red;text-align:center;font-size:x-large;'></div><p><br>\n";
	}elseif (strcmp($oper, "aktiviert") == 0){
		echo "<script type='text/javascript'>alert('".$message."');";
		echo "window.location.href = 'login.php';</script>";
	}
 	?>
        </div>
	<?php require('common_footer.php'); ?>
</div>
</body>

</html>
