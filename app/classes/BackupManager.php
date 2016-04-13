<?php
// require config.php
class BackupManager {
	
	private $_backupLocation;
	private $_backupType;
	private $_navindex_backup_path;
	
	function __construct() {
		global $ne2_config_info;
		$this->_backupLocation = $ne2_config_info['backup_root'];
		$this->_backupType = $ne2_config_info['backup_type'];
		$this->_navindex_backup_path = $ne2_config_info['navindex_backup_dir'];
	}
	
	function backup($file_path) {
		$bak_file_path = $this->_backupLocation . str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
		
		if($this->_backupType == 2) {
			// Timestamp
			$bakstr = '_' . date('YmdHis') . '.shtml';
			$bak_file_path = $bak_file_path . $bakstr;
		} elseif($this->_backupType == 1) {
			// .bak
			$bak_file_path = $bak_file_path . '.bak';
		}
		
		$dir = substr($bak_file_path, 0, strrpos($bak_file_path, "/"));
		if(!file_exists($bak_file_path)) {
			if(!is_dir($dir)) {
				umask(0022); // !!!
				mkdir($dir, 0755, TRUE);
			}
			if(!copy($file_path, $bak_file_path)) {
//				throw new Exception('Unable to create backup file!');
			}
			@chmod($bak_file_path, 0755);
		}
	}
	
	function restore($file_path, $backup_file_name) {
		// ...
		$bak_path_only = $this->_backupLocation . str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
		$bak_path_only = substr($bak_path_only, 0, strrpos($bak_path_only, '/'));
		$bak_path = $bak_path_only . '/' . $backup_file_name;
		if(!copy($bak_path, $file_path)) {
			throw new Exception('Unable to recover file!');
		}
		chmod($file_path, 0755);
	}
	
	function list_backup_versions($file_path) {
		// ...
		$bak_path = $this->_backupLocation . str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
		$retval = array();
		$path = substr($bak_path, 0, strrpos($bak_path, '/'));
                
                
                
		if(is_dir($path)) {
			if($dh = opendir($path)) {
				while(($file = readdir($dh)) !== FALSE) {
					if($file != '.' && $file != '..') {
						if(strpos($file, basename($file_path)) !== FALSE) {                                                       
							array_push($retval, array('file_name' => $file));
						}
					}
				}
			}
			closedir($dh);
		}
		return json_encode($retval);
	}
	
	function list_navindex_backups() {
		$prefix = 'navigationsindex.txt_bak_';
		$ret = array();
		$path = substr($this->_navindex_backup_path, 0, strrpos($this->_navindex_backup_path, '/'));
		if(is_dir($path)) {
			if($dh = opendir($path)) {
				while(($file = readdir($dh)) !== FALSE) {
					if($file != '.' && $file != '..') {
						if(strpos($file, $prefix) !== FALSE) {
							array_push($ret, array('file_name' => str_replace($prefix, '', $file)));
						}
					}
				}
			}
			closedir($dh);
		}
		return json_encode($ret);
	}
	
	function recover_navindex($backup_file_name) {
		$prefix = 'navigationsindex.txt_bak_';
		$bak_full_path = $this->_navindex_backup_path . $prefix . $backup_file_name;
		
		$internal_tree_file = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/navigationsindex_buffer.txt';
		$public_tree_file = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/navigationsindex.txt';
		
		if(!copy($bak_full_path, $internal_tree_file)) {
			throw new Exception('Unable to recover NavTree internal file!');
		}
		
		if(!copy($bak_full_path, $public_tree_file)) {
			throw new Exception('Unable to recover NavTree public file!');
		}
	}
	
}
?>
