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

$up_dir = $ne2_config_info['upload_dir'];
$up_dir_rel = str_replace($_SERVER['DOCUMENT_ROOT'], '', $up_dir);

if(isset($_POST['hidCustomData'])) {
	$upCustomData = $_POST['hidCustomData'];
	$arrCustomData = explode('|', $upCustomData);
	
	$up_dir = $arrCustomData[1] . '/';
	$up_dir_rel = str_replace($_SERVER['DOCUMENT_ROOT'], '', $up_dir);
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
		// Determine the filename to which we want to save this file:
		if(!is_dir($up_dir)) {
			mkdir($up_dir, 0755, TRUE);
		}
		
//		$prx = time() . '_';
		$prx = '';
		$oriname = str_replace(' ', '_', basename($_FILES['filAttachment']['name']));
		$newname = $prx . $oriname;
		// duplicate name?
//		while(file_exists($up_dir . $newname)) {
//			$newname = '_' . $newname;
//		}
		
		$thname = 'thumb_' . $newname;
		// duplicate name?
//		while(file_exists($up_dir . $thname)) {
//			$thname = '_' . $thname;
//		}
		
		$newnamepath = $up_dir . $newname;
		$thnamepath = $up_dir . $thname;

		// check file extension for script files
		@preg_match("/\.([^\.]+)$/", $newnamepath, $ext);
		if(@preg_match('/php|cgi|pl|asp|aspx/i', $ext[1])) {
			$newnamepath .= '.txt';
		}
				
		// Attempt to move the uploaded file to it's new home:
		if(!(move_uploaded_file($_FILES['filAttachment']['tmp_name'], $newnamepath))) {
			$json_res['error'] = 'A problem occurred during file upload!';
		} else {
			// It worked!
			chmod($newnamepath, 0755);
			if(is_image($newname)) {
				create_thumb($newnamepath, $thnamepath, 160, 160); // thumb size here!!
			}
			$json_res['info'] = 'Done!';
		}
	}
	echo(json_encode($json_res));
}
else {
	echo(json_encode(array('error' => 'There is no file to upload! I suggest you to drink some coffee!')));
}
?>
