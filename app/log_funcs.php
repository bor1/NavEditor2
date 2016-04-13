<?php
/**
 * Logger + login wartezeit, bei brutforce.
 * @TODO neu als Klasse schreiben. +param max file size.
 */
require_once('config.php');

$logfile = $ne2_config_info['log_path'] . $ne2_config_info['file_loginlog'];
$logHistory = $ne2_config_info['timeout_loghistory']; //Zeitperiode in Sekunden, die log beruecksichtigen muss.

function getOpName($operation) {
    switch ($operation) {
        case "mailSent":
            $op = "Mail versendet";
            break;
        case "nomail":
            $op = "Mailadresse bei dem User ist falsch/nicht gefunden";
            break;
        case "linkUsed":
            $op = "Link verwendet";
            break;
        case "pwChanged":
            $op = "Passwort geaendert";
            break;
        case "wrongKey":
            $op = "Key ist falsch";
            break;
        case "noKey":
            $op = "Key File existiert nicht";
            break;
        case "loginOk":
            $op = "Login erfolgreich";
            break;
        case "loginFail":
            $op = "Login gescheitert";
            break;
        case "sessionup":
            $op = "Sitzung refreshed";
            break;
        case "aktLinkUsed":
            $op = "aktivierungslink benutzt oder key eingegeben";
            break;
        default:
            $op = $operation;
    }
    return $op;
}

//log hinzufügen
function logadd($operation, $msg = null) {
    global $logfile;
    $op = getOpName($operation);
    if (isset($msg)) {
        $op .= ";  " . $msg;
    }
    $logentry = time() . "\t" . date("d.m.Y - H:i:s ") . "\tIP/Hostname:\t" . $_SERVER["REMOTE_ADDR"] . "\t" . gethostbyaddr($_SERVER["REMOTE_ADDR"]) . "\tReferrer: " . $_SERVER["HTTP_REFERER"] . "\t" . $op . "\n";
    //falls erfolgreich eingeloggt, alte gescheiterte versuche loeschen
    if ($operation == "loginOk" || $operation == "pwChanged") {
        delete_old_content($_SERVER["REMOTE_ADDR"]);
    }

    if (file_exists($logfile)) {

    }

    $save = fopen($logfile, "a");
    fputs($save, $logentry);
    fclose($save);

}

//anzahl von bestimmten Erreignissen/operation
function countLog($operation, $newcontent = null) {
    global $logfile;
    if (file_exists($logfile)) {
        //falls keine newcontent variable..
        if (!isset($newcontent)) {
            delete_old_content();
            $newcontent = file_get_contents($logfile);
        }
        $op = getOpName($operation);
        //nun nur die benoetige log eintraege ($op) auslesen
        $ipAdresse = str_replace('.', '\.', $_SERVER["REMOTE_ADDR"]);
        $result = array();
        preg_match_all('/([0-9]+).*\t' . $ipAdresse . '.*\t' . $op . '/', $newcontent, $result, PREG_PATTERN_ORDER);
        $counter = count($result[0]);

        $arr = array('counter' => $counter, 'lasttry' => end($result[1]));
        return $arr;
    } else {
        return array('counter' => 0, 'lasttry' => 0);
    }
}

function calcRestZeit($anzahlVersuche, $lastTry) {
    global $logHistory;
    $toWaitFunc = ($anzahlVersuche * pow(1.5, $anzahlVersuche)) + $lastTry - time();
    $maxTime = $logHistory;
    if ($toWaitFunc >= ($maxTime)) {
        $toWaitFunc = $maxTime + $lastTry - time();
    } elseif ($toWaitFunc < 0) {
        $toWaitFunc = 0;
    }
    return $toWaitFunc;
}

//wartezeit fuer login, nach x fehlvesuche.
function waitTimeForLogin() {
    global $logfile, $ne2_config_info;
    delete_old_content();
    $newcontent = file_get_contents($logfile);
    $logFail = countLog('loginFail', $newcontent);
    $pwChanged = countLog('pwChanged', $newcontent);
    $loginOk = countLog('loginOk', $newcontent);
    if (($pwChanged['lasttry'] > $logFail['lasttry']) || ($loginOk['lasttry'] > $logFail['lasttry'])) {
        $toWait = 0;
    } else {
        $toWait = calcRestZeit($logFail['counter'] - 3, $logFail['lasttry']);
    }
    return $toWait;
}

//veraltete log daten loeschen, optional nur von ipAdresse

function delete_old_content($ipAdresse = null) {
    global $logfile, $logHistory;
    if(!file_exists($logfile)){
        file_put_contents($logfile, '');
    }

    $subject = file_get_contents($logfile);

    $newcontent = "";
    preg_match_all('/([0-9]+).*/', $subject, $result, PREG_PATTERN_ORDER);

    //alles was aelter einer bestimmter zeit ist, loeschen
    $timeLimit = time() - $logHistory;
    for ($i = 0, $count = count($result[0]); $i < $count; $i++) {
        if ($timeLimit < $result[1][$i]) {
            //zeilen mit angegebener ipAdresse filtern
            if (!(isset($ipAdresse) && strpos($result[0][$i], $ipAdresse))) {
                $newcontent .= $result[0][$i] . "\n";
            }
        }
    }
    $save = fopen($logfile, "w");
    fputs($save, $newcontent);
    fclose($save);
}

function get_content_local($logfile){

}

function save_content_local($logfile, $newcontent){

}

?>