<?php
require_once('config.php');
require_once('../auth.php');

function is_image($file_name) {
	$fa = explode('.', $file_name);
	$ext = $fa[count($fa) - 1];
	if(preg_match('/jpg|jpeg|png|gif/i', $ext)) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function create_thumb($name, $filename, $new_w, $new_h) { // supports jpg, png and gif
	$system = explode(".", $name);
	if(preg_match("/jpg|jpeg/i", $system[count($system) - 1])) {
		$src_img = imagecreatefromjpeg($name);
	}
	if(preg_match("/png/i", $system[count($system) - 1])) {
		$src_img = imagecreatefrompng($name);
	}
	if(preg_match("/gif/i", $system[count($system) - 1])) {
		$src_img = imagecreatefromgif($name);
	}
	if(!isset($src_img)) {
		return; // not a supported image
	}
	$old_x = imagesx($src_img);
	$old_y = imagesy($src_img);
	if($old_x > $old_y) {
		$thumb_w = $new_w;
		$thumb_h = $old_y * ($new_h / $old_x);
	}
	if($old_x < $old_y) {
		$thumb_w = $old_x * ($new_w / $old_y);
		$thumb_h = $new_h;
	}
	if($old_x == $old_y) {
		$thumb_w = $new_w;
		$thumb_h = $new_h;
	}
	$dst_img = imagecreatetruecolor($thumb_w, $thumb_h);
	imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y); 
	if(preg_match("/png/", $system[count($system) - 1])) {
		imagepng($dst_img, $filename);
	} elseif(preg_match("/gif/", $system[count($system) - 1])) {
		imagegif($dst_img, $filename);
	} else {
		imagejpeg($dst_img, $filename);
	}
	imagedestroy($dst_img);
	imagedestroy($src_img);
	chmod($filename, 0755);
}


function get_ma_file_dir() {
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
		if(($ar['opt_name'] == 'URL_Mitarbeiter') && ($ar['opt_value'] != '')) {
			$v1 = substr($ar['opt_value'], 0, strrpos($ar['opt_value'], '/'));
			$ma_file_dir = $_SERVER['DOCUMENT_ROOT'] . $v1 . '/';
		}
	}
	if(!is_dir($ma_file_dir)) {
		$ma_file_dir = $_SERVER['DOCUMENT_ROOT'] . '/mitarbeiter/daten/';
	}
	
	return $ma_file_dir;
}

$ma_file_dir1 = get_ma_file_dir();
$ma_file_dir_ori = $ma_file_dir1 . 'original/';
@mkdir($ma_file_dir_ori, 0777, TRUE);

$ma_photo_file_name = '';
$ma_photo_file_path = '';

if(isset($_POST['hidCustomData'])) {
	$upCustomData = $_POST['hidCustomData'];
	$arrCustomData = explode('|', $upCustomData);
	
	$ma_photo_file_name = $arrCustomData[1];
	$ma_photo_file_path = $ma_file_dir1 . $ma_photo_file_name;
}

$json_res = array(
	'info' => '',
	'error' => ''
);

// real upload
if(count($_FILES)) {
	// Doublecheck that we really had a file:
	if(!($_FILES['filAttachment']['size'])) {
		$json_res['error'] = 'No actual file uploaded!';
	} else {
		$newnamepath = $ma_photo_file_path;
		
		// Attempt to move the uploaded file to it's new home:
		if(!(move_uploaded_file($_FILES['filAttachment']['tmp_name'], $newnamepath))) {
			$json_res['error'] = 'A problem occurred during file upload!';
		} else {
			// It worked!
			chmod($newnamepath, 0755);
			copy($newnamepath, $ma_file_dir_ori . $ma_photo_file_name);
			create_thumb($newnamepath, $newnamepath, 180, 180); // thumb size here!!
			$json_res['info'] = 'Done!';
		}
	}
	echo(json_encode($json_res));
}
else {
	echo(json_encode(array('error' => 'There is no file to upload! I suggest you to drink some coffee!')));
}
?>
