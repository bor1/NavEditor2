<?php
require_once('config.php');
require_once('../auth.php');

$univis_id = '';

function file_get_contents_utf8($fn) {
	$content = file_get_contents($fn);
	return mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, 'UTF-8, ISO-8859-1', TRUE));
}

function get_ma_file_dir() {
	global $univis_id;
	
	// get ma path from univis.conf
	$fpath = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/univis.conf';
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
					'opt_name' => $arr_opts[0],
					'opt_value' => $arr_opts[1]
				);
				array_push($retv, $opt);
			}
		}
	}
	fclose($fh);
	
	$ma_file_dir = '';
	
	foreach($retv as $ar) {
		if(($ar['opt_name'] == 'Datenverzeichnis') && ($ar['opt_value'] != '')) {
			$v1 = substr($ar['opt_value'], 0, strrpos($ar['opt_value'], '/'));
			$ma_file_dir = $_SERVER['DOCUMENT_ROOT'] . $v1 . '/mitarbeiter-einzeln/';
		}
		if($ar['opt_name'] == 'UnivISId' || $ar['opt_name'] == 'UnivISOrgNr') {
			$univis_id = $ar['opt_value'];
		}
	if(!is_dir($ma_file_dir)) {
		$ma_file_dir = $_SERVER['DOCUMENT_ROOT'] . '/univis-daten/mitarbeiter-einzeln/';
	}
	
	return $ma_file_dir;
}

$ma_file_dir1 = get_ma_file_dir();
if(!file_exists($ma_file_dir1)) {
	mkdir($ma_file_dir1, 0777, TRUE);
}

$oper = $_REQUEST['oper'];
$filename = $_REQUEST['ma_file_name'];
$ma_file = $ma_file_dir1 . $filename;

switch($oper) {
	case 'get_ma_file_list':
		$flist = array();
		$conf_dir = $ma_file_dir1;
		if(is_dir($conf_dir)) {
			if($dh = opendir($conf_dir)) {
				while(($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..') {
						if(strtolower(substr($file, strrpos($file, '.') + 1, 3)) == 'txt') {
							array_push($flist, array('file_name' => $file));
						}
					}
				}
			}
			closedir($dh);
		}
		echo(json_encode($flist));
		break;
	case 'get_content':
		$file_content = file_get_contents_utf8($ma_file);
		$ret = str_replace(array('<!--#', '<!--', '-->'), array('<comment_ssi>', '<comment>', '</comment>'), $file_content);
		$photo_url = '';
		$photo_path = $ma_file_dir1 . str_replace('.txt', '.jpg', $filename);
		if(file_exists($photo_path)) {
			$photo_url = str_replace($_SERVER['DOCUMENT_ROOT'], '', $photo_path);
		}
		$rv = array(
			'photo_url' => $photo_url,
			'file_content' => $ret
		);
		echo(json_encode($rv));
		break;
	case 'update_content':
		$new_file_content = $_POST['file_content'];
		if(get_magic_quotes_gpc()) {
			$new_file_content = stripslashes($new_file_content);
		}
		$new_file_content = str_replace(array('<comment_ssi>', '<comment>', '</comment>'), array('<!-' . '-#', '<!--', '-->'), $new_file_content);
		if(file_put_contents($ma_file, $new_file_content)) {
			echo('Update done!');
		} else {
			echo('Update file error!');
		}
		break;
	case 'delete':
		@unlink($ma_file);
		echo('Done!');
		break;
	case 'get_univis_id':
		echo($univis_id);
		break;
	case 'set_univis_id':
		$new_univis_id = $_POST['new_univis_id'];
		$new_univis_id_line = "UnivISId\t" . $new_univis_id . "\n";
		
		$before_part = '';
		$after_part = '';
		$found_line = FALSE;
		
		$fpath = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/univis.conf';
		$fh = fopen($fpath, 'r') or die('Cannot open file!');
		while(!feof($fh)) {
			$oline = fgets($fh);
			$pline = str_replace(array("\r", "\n", "\r\n"), '', ltrim($oline));
			if((strlen($pline) == 0) || (substr($pline, 0, 1) == '#')) {
				if($found_line) {
					$after_part .= $oline;
				} else {
					$before_part .= $oline;
				}
				continue; // ignore comments and empty rows
			}
			
			if(!$found_line) {
				$arr_opts = preg_split('/\t|\s{2,}/', $pline);
				if($arr_opts[0] == 'UnivISId') {
					$found_line = TRUE;
					continue;
				} else {
					$before_part .= $oline;
				}
			} else {
				$after_part .= $oline;
			}
		}
		fclose($fh);
		
		$new_content = $before_part . $new_univis_id_line . $after_part;
		file_put_contents($fpath, $new_content);
		echo('Neue UnivIS-ID wurde gesperchert.');
		break;
	default:
		break;
}
?>
