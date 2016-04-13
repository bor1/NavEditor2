<?php
require_once('config.php');
require_once('../auth.php');
require('classes/Snoopy.class.php');
require_once('classes/FileManager.php');

$fm = new FileManager();

ini_set('zend.ze1_compatibility_mode', 0);

function http_get_file($url) {
    // now use Snoopy
    $snoopy = new Snoopy();
    if($snoopy->fetch($url)) {
        return $snoopy->results;
    } else {
        return FALSE;
    }

/*		$url_stuff = parse_url($url);
    $port = isset($url_stuff['port']) ? $url_stuff['port'] : 80;

    $fp = fsockopen($url_stuff['host'], $port);
    if(!$fp) {
        return FALSE;
    }

    $query  = 'GET ' . $url_stuff['path'] . " HTTP/1.0\n";
    $query .= 'Host: ' . $url_stuff['host'];
    $query .= "\n\n";

    fwrite($fp, $query);

    $buffer = '';
    while($line = fread($fp, 1024)) {
        $buffer .= $line;
    }

    fclose($fp);

    preg_match('/Content-Length: ([0-9]+)/', $buffer, $parts);
    return substr($buffer, -$parts[1]);*/

/*		if(function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');

        $content = curl_exec($ch);

        curl_close($ch);
        return $content;
    } else {
        return FALSE;
    }*/
}

$chlog = '';
$updates_info = array(
    'current_version' => $ne2_config_info['version'],
    'has_stable_update' => FALSE,
    'stable_version' => '',
    'stable_chlog' => '',
    'has_test_update' => FALSE,
    'test_version' => '',
    'test_chlog' => '',
    'error' => ''
);

function check_update() {
    global $ne2_config_info;
    global $updates_info;

    $err = FALSE;
    $url = $ne2_config_info['update_url'] . 'ver.txt';
    try {
        $ver = trim(http_get_file($url));
        if($ver) {
            $verarr = explode('|', $ver);
            if(strcmp($verarr[0], 'NE2_LATEST_VERSION') != 0) { // error reading file
                $err = TRUE;
            } else {
                $ver = $verarr[1]; // latest stable version
                $updates_info['stable_version'] = $ver;
                $ver = (int)str_replace('.', '', $ver);
            }

            $cur_ver = $ne2_config_info['version'];
            $cur_ver = (int)str_replace('.', '', $cur_ver);

            if(($err === FALSE)) { // has stable update
                $chlog = http_get_file($ne2_config_info['update_url'] . 'changelog_' . $updates_info['stable_version'] . '.txt');
                $chlog = str_replace(array("\r", "\n", "\r\n"), '<br />', $chlog);
                if($cur_ver < $ver)
                    $updates_info['has_stable_update'] = TRUE;
                $updates_info['stable_chlog'] = $chlog;
            }

            if(strcmp($verarr[2], 'NE2_TEST_VERSION') == 0) {
                $tver = $verarr[3];
                if($tver == '') {
                    $tver1 = 0;
                } else {
                    $tver1 = (int)str_replace('.', '', $tver);
                }
                // if($cur_ver < $tver1) {
                    $updates_info['has_test_update'] = TRUE;
                    $updates_info['test_version'] = $tver;
                    $tchlog = http_get_file($ne2_config_info['update_url'] . 'changelog_' . $updates_info['test_version'] . '.txt');
                    $tchlog = str_replace(array("\r", "\n", "\r\n"), '<br />', $tchlog);
                    $updates_info['test_chlog'] = $tchlog;
                // }
            }
        } else {
            $updates_info['error'] = 'Error checking update!';
        }
    } catch(Exception $e) {
        $err = TRUE;
    }
}

function do_update($file_name) {
    global $ne2_config_info;
    global $fm;
    // download
    $fname = $ne2_config_info['app_path'] . 'ne2_update_download.zip'; // temp filename
    $fh = fopen($fname, 'w');
    $url = $ne2_config_info['update_url'] . $file_name;
    $data = http_get_file($url);
    fwrite($fh, $data);
    fclose($fh);

    // Sichern der alten Configfiles
    for($i=0;$i<count($ne2_config_info['live_update_backupfiles']);$i++) {
        $backupconfigfile = $ne2_config_info['config_path'].$ne2_config_info['live_update_backupfiles'][$i];
        $fm->backupCurrentConfigFile($backupconfigfile);
    }




    // unzip
    $zip = new ZipArchive();
    $zor = $zip->open($fname);
    if($zor) {
        $rpath = substr($ne2_config_info['app_path'], 0, strlen($ne2_config_info['app_path']) - 11) . '';
        $zip->extractTo($rpath); // !!!
        $zip->close();
        @unlink($fname);

        // Und hier ggf. die alten Configs wieder einspielen
        // ggf. neue configs in _$name speichern

        for($i=0;$i<count($ne2_config_info['live_update_backupfiles']);$i++) {
            $backupconfigfile = $ne2_config_info['config_path'].$ne2_config_info['live_update_backupfiles'][$i];
            $fm->restoreCurrentConfigFile($backupconfigfile);
        }

        //rename old contactdata.conf
        $tmp_path_for_contactdata = $_SERVER['DOCUMENT_ROOT'] . "/vkdaten/";
        if(file_exists($_SERVER['DOCUMENT_ROOT'] . "/vkdaten/contactdata.conf")){
            rename($tmp_path_for_contactdata."contactdata.conf", $tmp_path_for_contactdata."contactdata.conf.bak");
        }

        return 'Update done!';
    } else {
        return 'Update failed! Possible reason: ' . $zor;
    }
}

$oper = $_REQUEST['oper'];
switch($oper) {
    case 'check_update':
        check_update();
        echo(json_encode($updates_info));
        break;
    case 'do_update':
        $v = $_REQUEST['uv'];
        echo(do_update('NavEditor2_' . $v . '.zip'));
        break;
    default:
        echo('Parameter error!');
        break;
}
?>