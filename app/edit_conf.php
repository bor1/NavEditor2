<?php
require_once('config.php');
require_once('../auth.php');

$fpath = $ne2_config_info['usual_configs_path'];
$htusers_file = $fpath . '.htusers';
$hthosts_file = $fpath . 'hthosts';

function htpasswd($pwd) {
    return crypt(trim($pwd), base64_encode(CRYPT_STD_DES));
}

function add_to_retval($arr) {
    global $retval;
    $arr1 = array(
        'item' => \NavTools::ifsetor($arr[0]),
        'value' => \NavTools::ifsetor($arr[1])
    );
    array_push($retval, $arr1);
}

$oper = $_REQUEST['oper'];
$filename = $_REQUEST['conf_file_name'];
$fpath = $fpath . $filename;
if($filename == 'ne2_config.conf') {
    $fpath = $ne2_config_info['config_path'] .'ne2_config.conf';
}

switch($oper) {
    case 'get_feedimport':
        $ret = array(
            'feeds' => array(),
            'options' => array()
        );
        $fh = fopen($fpath, 'r') or die('Cannot open file!');
        while(!feof($fh)) {
            $line = fgets($fh);
            $line = trim($line);
            if((strlen($line) == 0) || (substr($line, 0, 1) == '#')) {
                continue; // ignore comments and empty rows
            }
            // proc feed list
            if(substr($line, 0, 5) == 'Feed-') {
                $arr_feeds = preg_split('/\t/', $line); // tab separated
                $feed_id_str = $arr_feeds[0];
                $feed_id_arr = explode('-', $feed_id_str);
                $feed_id = $feed_id_arr[1];

                $feed_title = html_entity_decode($arr_feeds[1]);
                $feed_url = $arr_feeds[2];
                $feed = array(
                    'id' => $feed_id,
                    'title' => $feed_title,
                    'url' => $feed_url
                );
                array_push($ret['feeds'], $feed);
            } else {
                $arr_opts = preg_split('/\t/', $line);
                $opt = array(
                    'opt_name' => \NavTools::ifsetor($arr_opts[0]),
                    'opt_value' => \NavTools::ifsetor($arr_opts[1])
                );
                array_push($ret['options'], $opt);
            }
        }
        fclose($fh);
        echo(json_encode($ret));
        break;
    case 'set_feedimport':
        $json_data = $_REQUEST['jsonData'];
        $json_arr = json_decode(stripslashes($json_data), TRUE);
        $stack = array();
        $new_file_content = '';
        $feed_set = FALSE;
        $opt_set = FALSE;

        $fh = fopen($fpath, 'r') or die("Cannot open file!");
        while(!feof($fh)) {
            $line = fgets($fh); // read a line
            $line = trim($line);
            if(strlen($line) == 0 || substr($line, 0, 1) == '#') {
                $new_file_content .= $line . "\n";
            } else {
                // to add real config-data
                if((substr($line, 0, 4) == 'Feed') && ($feed_set === FALSE)) {
                    foreach($json_arr['feeds'] as $f) {
                        $feed_str = "Feed-" . $f['id'];
                        $feed_str .= "\t" . htmlentities($f['title']);
                        $feed_str .= "\t" . $f['url'];
                        $new_file_content .= $feed_str . "\n";
                    }
                    $feed_set = TRUE;
                } else {
                    if($opt_set === FALSE) {
                        foreach($json_arr['options'] as $o) {
                            $opt = $o['opt_name'] . "\t" . $o['opt_value'];
                            $new_file_content .= $opt . "\n";
                        }

                        // set general items as options
                        foreach($json_arr['general_items'] as $g) {
                            $gi = $g['gi_name'] . "\t" . $g['gi_value'];
                            $new_file_content .= $gi . "\n";
                        }

                        $opt_set = TRUE;
                    }
                }
            }
        }
        fclose($fh);
        $fh1 = fopen($fpath . '1', 'w') or die('Cannot open file!'); // write to a temp-file
        fwrite($fh1, $new_file_content);
        fclose($fh1);
        copy($fpath, $fpath . '.bak'); // make backup of ori-file
        unlink($fpath); // delete ori-file
        rename($fpath . '1', $fpath); // rename temp-file to ori-filename
        chmod($fpath, 0755); // !!!
        echo('Update done!');
        break;

    // common conf-files
    case 'get_conf':
        $retv = array();
        $to_concat = FALSE;
        $fh = fopen($fpath, 'r') or die('Cannot open file!');
        while(!feof($fh)) {
            $oline = fgets($fh);
            $pline = str_replace(array("\r", "\n", "\r\n"), '', ltrim($oline));
            if((strlen($pline) == 0) || (substr($pline, 0, 1) == '#')) {
                continue; // ignore comments and empty rows
            }

            if(substr($pline, strlen($pline) - 2, 2) == " \\") {
                // concat next lines to form the value
                if($to_concat === FALSE) {
                    $to_concat = TRUE;
                    $arr_opts1 = preg_split('/\t|\s{2,}/', $pline);
                    $opt1 = array(
                        'opt_name' => $arr_opts1[0],
                        'opt_value' => str_replace(" \\", "", $arr_opts1[1])
                    );
                    continue;
                } else {
                    $opt1['opt_value'] .= " " . str_replace(" \\", "", $pline);
                }
            } else {
                if($to_concat) {
                    $opt1['opt_value'] .= " " . $pline; // the last line
                    array_push($retv, $opt1);
                    $to_concat = FALSE;
                } else {
                    $arr_opts = preg_split('/\t|\s{2,}/', $pline);
                    $opt = array(
                        'opt_name' => \NavTools::ifsetor($arr_opts[0]),
                        'opt_value' => \NavTools::ifsetor($arr_opts[1])
                    );
                    array_push($retv, $opt);
                }
            }
        }
        fclose($fh);
        echo(json_encode($retv));
        break;
    case 'set_conf':
//         //test, set conf by ConfigManager.php class
//        $file_conf_editor = new ConfigManager($fpath);
//        $json_data = $_REQUEST['jsonData'];
//        $json_arr = json_decode($json_data, TRUE);
//        $associative_array_to_set = array();
//        //make assotiative
//        foreach($json_arr as $ji) {
//            $associative_array_to_set[$ji['opt_name']] = $ji['opt_value'];
//        }
//
//        $file_conf_editor->set_conf_items($associative_array_to_set);
//        echo('Update done!');
//        break;

        $json_data = $_REQUEST['jsonData'];
        $json_arr = json_decode($json_data, TRUE);
        $new_file_content = '';
        $concating = FALSE;
        $ori_names = array();

        $fh0 = fopen($fpath, 'r') or die('Cannot open file!');
        while(!feof($fh0)) {
            $oline = fgets($fh0);
            $pline = str_replace(array("\r", "\n", "\r\n"), '', ltrim($oline));
            if((strlen($pline) == 0) || (substr($pline, 0, 1) == '#')) {
                $new_file_content .= $oline; // preserve original texts
                continue;
            }

            if(substr($pline, strlen($pline) - 2, 2) == " \\") {
                // concat next lines to form the value
                if($concating === FALSE) {
                    $concating = TRUE;
                    $arr_opts1 = preg_split('/\t|\s{2,}/', $pline);
                    $opt_name = $arr_opts1[0];
                    array_push($ori_names, $opt_name);
                    foreach($json_arr as $ji) {
                        if($ji['opt_name'] == $opt_name) {
                            $opt_line = $ji['opt_name'] . "\t" . $ji['opt_value'] . "\r\n";
                            $new_file_content .= $opt_line;
                            break;
/*						} else {
                            $opt_line = "\r\n" . $ji['opt_name'] . "\t" . $ji['opt_value'] . "\r\n";
                            $new_file_content .= $opt_line;*/
                        }
                    }
                } else {
                    continue;
                }
            } else { // normal conf-item or last line of multiline-value
                if($concating) {
                    $concating = FALSE;
                    continue;
                } else {
                    $arr_opts = preg_split('/\t|\s{2,}/', $pline);
                    $opt_name = $arr_opts[0];
                    array_push($ori_names, $opt_name);
                    foreach($json_arr as $ji) {
                        if($ji['opt_name'] == $opt_name) {
                            $opt_line = $ji['opt_name'] . "\t" . $ji['opt_value'] . "\r\n";
                            $new_file_content .= $opt_line;
                            break;
/*						} else {
                            $opt_line = "\r\n" . $ji['opt_name'] . "\t" . $ji['opt_value'] . "\r\n";
                            $new_file_content .= $opt_line;*/
                        }
                    }
                }
            }

        }
        foreach($json_arr as $ji) {
            if(in_array($ji['opt_name'], $ori_names)) {
                continue;
            } else {
                $new_file_content .= "\r\n" . $ji['opt_name'] . "\t" . $ji['opt_value'];
            }
        }

        fclose($fh0);
        $fh1 = fopen($fpath . '.tmp', 'w') or die('Cannot open file!'); // write to a temp-file
        fwrite($fh1, $new_file_content);
        fclose($fh1);
        copy($fpath, $fpath . '.bak'); // make backup of ori-file
        unlink($fpath); // delete ori-file
        rename($fpath . '.tmp', $fpath); // rename temp-file to ori-filename
        chmod($fpath, 0755); // !!!
        echo('Update done!');
        break;

    case 'get_conf_list':
        $flist = array();
        $conf_dir = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten';
        if(is_dir($conf_dir)) {
            if($dh = opendir($conf_dir)) {
                while(($file = readdir($dh)) !== false) {
                    if($file != '.' && $file != '..' && $file != 'ne2_config.conf'

                        && is_bool(strrpos($file,"~"))  && is_bool(strrpos($file,".bk"))
                         && is_bool(strrpos($file,".bak")) ) {
                        if(strtolower(substr($file, strrpos($file, '.') + 1, 4)) == 'conf') {
                            array_push($flist, array('file_name' => $file));
                        }
                    }
                }
            }
            closedir($dh);
        }
        // htusers and hthosts
        array_push($flist, array('file_name' => '.htusers'));
        array_push($flist, array('file_name' => 'hthosts'));
        echo(json_encode($flist));
        break;

    case 'get_vorlagen':
        $fh = fopen($fpath, 'r') or die('Cannot open file!');
        $retval = array();

        while(!feof($fh)) {
            $line = fgets($fh);
            $line = trim($line);
            if((strlen($line) == 0) || (substr($line, 0, 1) == '#')) {
                continue;
            }
            $pat = '/\s+/';
            $arr_split = preg_split($pat, $line);
            add_to_retval($arr_split);
        }
        fclose($fh);
        echo(json_encode($retval));
        break;
    case 'set_vorlagen':
        $jsn_data = $_REQUEST['json_data'];
        $jsn_arr = json_decode(stripslashes($jsn_data), TRUE);
        $fstr = '';

        for($i = 0; $i < count($jsn_arr); $i++) {
            $fstr .= $jsn_arr[$i]['item'] . "\t" . $jsn_arr[$i]['value'] . "\n";
        }

        file_put_contents($fpath, $fstr);
        echo('Update done!');
        break;
    case 'get_htusers':
        $ret = array();
        // list all htusers
        if(file_exists($htusers_file)) {
            $f1 = file($htusers_file);
            for($j = 0; $j < count($f1); $j++) {
                list($uname, $passhash) = explode(':', trim($f1[$j]));
                array_push($ret, array(
                    'username' => $uname
                ));
            }
        }
        echo(json_encode($ret));
        break;
    case 'add_htuser':
        $username = $_POST['username'];
        $password = $_POST['password'];
        if($username != '' && $password != '') {
            $line = $username . ":" . htpasswd($password) . "\n";
            if(!file_exists($htusers_file)) {
                touch($htusers_file);
            }

            if(is_writable($htusers_file)) {
                if(!($fh = fopen($htusers_file, 'a'))) {
                    echo('Unable to open htusers file!');
                }
                if(fwrite($fh, $line) === FALSE) {
                    echo('Unable to write to htusers file!');
                }
                fclose($fh);
            }
            echo('Done!');
        } else {
            echo('Username und Password duerfen nicht leer sein!');
        }
        break;
    case 'delete_htuser':
        $username = $_POST['username'];
        $fa = file($htusers_file);
        for($k = 0; $k < count($fa); $k++) {
            if(NavTools::startsWith(trim($fa[$k]), $username)) {
                unset($fa[$k]);
            }
        }
        $fc = join($fa);
        file_put_contents($htusers_file, $fc);
        echo($username . ' wurde geloescht.');
        break;
    case 'get_hthosts':
        $ret = array();
        // list all hthosts
        if(file_exists($hthosts_file)) {
            $f1 = file($hthosts_file);
            for($j = 0; $j < count($f1); $j++) {
                array_push($ret, array(
                    'host' => $f1[$j]
                ));
            }
        }
        echo(json_encode($ret));
        break;
    case 'add_hthost':
        $host = $_POST['host'] . "\n";
        if(!file_exists($hthosts_file)) {
            touch($hthosts_file);
        }

        if(is_writable($hthosts_file)) {
            if(!($fh = fopen($hthosts_file, 'a'))) {
                echo('Unable to open htusers file!');
            }
            if(fwrite($fh, $host) === FALSE) {
                echo('Unable to write to htusers file!');
            }
            fclose($fh);
        }
        echo('Done!');
        break;
    case 'delete_hthost':
        $host = $_POST['host'];
        $fa = file($hthosts_file);
        for($k = 0; $k < count($fa); $k++) {
            if(NavTools::startsWith($fa[$k], $host)) {
                unset($fa[$k]);
            }
        }
        $fc = join($fa);
        file_put_contents($hthosts_file, $fc);
        echo('Done!');
        break;
    default:
        break;
}

?>
