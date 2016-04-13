<?php
// require config.php!
class FileHandler {
	private $_arr_json;
	private $_buffer;
	private $_section;
	private $_navDataText;
	private $_navQuickLinkText;
	private $_navDisplayLinkText;
	private $_templateFilePath;
	private $_logger;
	private $_sort_array;
	private $_arr_root_node;
	private $_navindex_backup_dir;
	private $_title_icon_pattern;
	
	public function __construct() {
		global $ne2_config_info;
		
		$this->_arr_json = array(
			'A' => array(),
			'Z' => array(),
			'S' => array()
		);
		$this->_buffer = '';
		$this->_section = 0;
		$this->_navDataText = '';
		$this->_navQuickLinkText = '';
		$this->_navDisplayLinkText = '';
		$this->_templateFilePath = $ne2_config_info['app_path'] . 'data/templates/seitenvorlage.html';
		if(!file_exists($this->_templateFilePath)) {
			$this->_templateFilePath = $ne2_config_info['app_path'] . 'data/templates/_seitenvorlage.html'; // the fail-safe
		}
		$this->_logger = null;
		$this->_sort_array = array(); // to sort and contain the partial parse result
		array_push($this->_sort_array, array('key' => 'A0')); // placeholder for U
		$this->_arr_root_node = array();
		$this->_navindex_backup_dir = $ne2_config_info['navindex_backup_dir'];
		$this->_title_icon_pattern = '/<img alt="(.+)" src="(.+)" title="(.+)" class="(.+)" \/>/i';
	}
	
	public function setTemplateFile($templPath) {
		$this->_templateFilePath = $templPath;
	}
	
	public function __destruct() {
		unset($this->arr_json);
	}
	
	public function setLogger($logger) {
		$this->_logger = $logger;
	}
	
	private function _nodeType($nodeKey) {
		$firstLetter = substr($nodeKey, 0, 1);
		return strtoupper($firstLetter);
	}
	
	private function _make_sort_key($ks) {
		$pad_len = 3; // max 999 item no. -_-
		$ksa = explode('-', $ks);
		for($i = 0; $i < count($ksa); $i++) {
			$t = $ksa[$i];
			$t = substr($t, 0, 1) . str_pad(substr($t, 1), $pad_len, '0', STR_PAD_LEFT);
			$ksa[$i] = $t;
		}
		return implode($ksa, '-');
	}
	
	function _extractKeyString($key_string) {
		$ret_arr = array(
			'key' => '',
			'info_text' => FALSE,
			'url' => FALSE,
			'sort_key' => ''
		);
		if(strtoupper($key_string) == 'U') {
			$ret_arr['key'] = 'U';
			return $ret_arr;
		}
		$test = substr($key_string, strlen($key_string) - 1);
		if(is_numeric($test)) {
			$ret_arr['key'] = $key_string;
			$ret_arr['sort_key'] = $this->_make_sort_key($key_string);
			return $ret_arr;
		} else {
			$ret_arr['key'] = substr($key_string, 0, strrpos($key_string, '-'));
			$sp = explode('-', $key_string);
			if(strtoupper($sp[count($sp) - 1]) == 'URL') {
				$ret_arr['url'] = TRUE;
			} elseif(strtoupper($sp[count($sp) - 1]) == 'INFOTEXT') {
				$ret_arr['info_text'] = TRUE;
			}
			return $ret_arr;
		}
	}
	
	private function _updateSortArray($key, $vk, $vv) {
		foreach($this->_sort_array as &$sa) {
			if($sa['key'] == $key) {
				$sa[$vk] = $vv;
				break;
			}
		}
		
		if($key == 'U') {
			$this->_arr_root_node[$vk] = $vv;
		}
	}
	
	private function _filterChars($source) {
		$filter = array('/', '\\');
		return str_replace($filter, '', trim($source));
	}
	
	private function _parseLine($line_string) {
		$arr_buf = explode("\t", $line_string);
		if(count($arr_buf) <= 1) {
			return FALSE;
		}
		
		$arr_line = array();
		$key_string = $this->_filterChars($arr_buf[0]); // filter the bad chars
		$key_ext = $this->_extractKeyString($key_string);
		if(($key_ext['url'] == FALSE) && ($key_ext['info_text'] == FALSE)) {
			$arr_line['sort_key'] = $key_ext['sort_key']; // Key for sorting
			$arr_line['key'] = $key_ext['key']; // Key
			$title_text = $arr_buf[1];
			$title_icon = '';
			$title_icon_alt = '';
			$title_icon_title = '';
			$title_display = ''; // for even more html in the title, such as span
			if(strpos($title_text, '<img') !== FALSE) {
				// the title has an icon
				preg_match($this->_title_icon_pattern, $title_text, $tts);
				$title_text = trim(preg_replace($this->_title_icon_pattern, '', $title_text));
				$title_icon_alt = $tts[1];
				$title_icon = isset($tts[2]) ? $tts[2] : '';
				$title_icon_title = isset($tts[3]) ? $tts[3] : '';
			} elseif(strip_tags($title_text) != $title_text) {
				$title_display = $title_text;
			}
//			$arr_line['title'] = html_entity_decode($title_text, ENT_COMPAT, 'UTF-8'); // Title
			$arr_line['title'] = html_entity_decode(strip_tags($title_text), ENT_COMPAT, 'UTF-8'); // Title text
			$arr_line['title_display'] = $title_display;
			$arr_line['child'] = array(); // Hold for child-folders
			
			$arr_line['alias'] = isset($arr_buf[2]) ? $this->_filterChars($arr_buf[2]) : '';
			$arr_line['accesskey'] = isset($arr_buf[3]) ? $arr_buf[3] : '';
			$arr_line['email'] = isset($arr_buf[4]) ? $arr_buf[4] : '';
			
			$arr_line['path'] = '';
			$arr_line['quicklink'] = FALSE;
			$arr_line['displaylink'] = FALSE;
			
			$arr_line['url'] = '';
			$arr_line['info_text'] = '';
			$arr_line['title_icon'] = $title_icon;
			$arr_line['title_icon_alt'] = $title_icon_alt;
			$arr_line['title_icon_title'] = $title_icon_title;
			
			if($key_ext['key'] != 'U') {
				array_push($this->_sort_array, $arr_line);
			} else {
				$this->_arr_root_node = $arr_line;
			}
		} else {
			if($key_ext['url'] == TRUE) {
				$mkey = $key_ext['key'];
				$vkey = 'url';
				$vval = $arr_buf[1];
				$this->_updateSortArray($mkey, $vkey, $vval);
			}
			
			if($key_ext['info_text'] == TRUE) {
				$mkey = $key_ext['key'];
				$vkey = 'info_text';
				$vval = $arr_buf[1];
				$this->_updateSortArray($mkey, $vkey, $vval);
			}
		}
		
		return TRUE;
	}
	
	function _prepareTree() {
		// !!!
		sort($this->_sort_array);
		
		$this->_sort_array[0] = $this->_arr_root_node;
		
		foreach($this->_sort_array as $sa) {
			$this->_addToJSONArray($sa);
		}
	}
	
	private function _addToJSONArray($arr_part) {
		$check_key = $arr_part['key'];
		$arr_check_key = explode("-", $check_key);
		$nodeType = $this->_nodeType($check_key);
		
		switch(count($arr_check_key)) {
			case 1: // A1
				switch($nodeType) {
					case 'U':
					case 'A':
						array_push($this->_arr_json['A'], $arr_part);
						break;
					case 'Z':
						array_push($this->_arr_json['Z'], $arr_part);
						break;
					case 'S':
						array_push($this->_arr_json['S'], $arr_part);
						break;
					default:
						return FALSE;
						break;
				}
				break;
			default:
				$target = &$this->_arr_json['A'];
				for($i = 1; $i < count($arr_check_key); $i++) { // start from 2nd elem.
					$target = &$target[count($target) - 1]['child'];
				}
				array_push($target, $arr_part);
				break;
		}
	}
	
	private function _setQuickLink($key, &$nodes) {
		for($i = 0; $i < count($nodes); $i++) {
			if(strcmp($nodes[$i]['key'], $key) == 0) {
				$nodes[$i]['quicklink'] = TRUE;
				return;
			}
			if(count($nodes[$i]['child']) > 0) {
				$this->_setQuickLink($key, $nodes[$i]['child']);
			}
		}
	}
	
	private function _setDisplayLink($key, &$nodes) {
		for($i = 0; $i < count($nodes); $i++) {
			if(strcmp($nodes[$i]['key'], $key) == 0) {
				$nodes[$i]['displaylink'] = TRUE;
				return;
			}
			if(count($nodes[$i]['child']) > 0) {
				$this->_setQuickLink($key, $nodes[$i]['child']);
			}
		}
	}
	
	public function loadJSONFromFile($file_path) {
		$fh = fopen($file_path, 'r') or die("Cannot open Nav-Tree file! - $php_errormsg");
		while(!feof($fh)) {
			$this->_buffer = fgets($fh);
			$this->_buffer = trim($this->_buffer);
			// ignore blank lines and comments
			if((strlen($this->_buffer) == 0) || (substr($this->_buffer, 0, 1) == '#')) {
				continue;
			}
			
			switch($this->_buffer) {
				case '<navigation>':
					$this->_section = 1;
					break;
				case '<quicklinks>':
					$this->_section = 2;
					break;
				case '<accesskeys>':
					$this->_section = 3;
					break;
				case '<displaylinks>':
					$this->_section = 4;
					break;
				case '</navigation>':
					$this->_prepareTree();
					break;
				case '</quicklinks>':
				case '</accesskeys>':
				case '</displaylinks>':
					$this->_section = 0;
					break;
				default:
					if($this->_section == 1) {
						if(!$this->_parseLine($this->_buffer)) {
							continue;
						}
					}
					if($this->_section == 2) {
						$this->_setQuickLink($this->_buffer, $this->_arr_json['A']);
					}
					if($this->_section == 4) {
						$this->_setDisplayLink($this->_buffer, $this->_arr_json['A']);
					}
					break;
			}
		}
		fclose($fh);
		
		return $this->_arr_json;
	}
	
	public function setJSONArray($jsonArray) {
		$this->_arr_json = $jsonArray;
	}
	
	private function _doLog($logText) {
		if(!is_null($this->_logger)) {
			$this->_logger->Log($logText);
		}
	}
	
	private function _file_exists_case($strUrl) {
		$realPath = str_replace('\\', '/', realpath($strUrl));
		if(file_exists($strUrl) && $realPath == $strUrl) {
			return 1; // File exists, with correct case
		} elseif(file_exists($realPath)) {
			return 2; // File exists, but wrong case
		} else {
			return 0; // File does not exist
		}
	}
	
	public function checkPath($path) {
		$path = $_SERVER['DOCUMENT_ROOT'] . $path;
		$dir = substr($path, 0, strrpos($path, "/"));
		if(!file_exists($path)) {
			if(!is_dir($dir)) {
				$this->_doLog('mkdir: ' . $dir);
				umask(0022); // !!!
				mkdir($dir, 0755, TRUE);
			}
			$this->_doLog('copy: ' . $this->_templateFilePath . ' -> ' . $path);
			@copy($this->_templateFilePath, $path);
			@chmod($path, 0755);
			return FALSE;
		} else {
			return TRUE; // file exists, by creating one should then specify an alias
		}
	}
	
	private function _pad_num($num, $n) {
		return str_pad($num, $n, '0', STR_PAD_LEFT);
	}
	
	private function _buildNavDataText($arrPart) {
		static $indent = 0;
		for($i = 0; $i < count($arrPart); $i++) {
			$indent++;
			$ftabs = str_repeat("\t", $indent);
			$this->_navDataText .= $ftabs . $arrPart[$i]['key'];
			$ttl1 = htmlentities($arrPart[$i]['title'], ENT_COMPAT, 'UTF-8');
			if($arrPart[$i]['title_icon'] != '') {
				$ttl1 = '<img alt="' . $arrPart[$i]['title_icon_alt'] . '" src="' . $arrPart[$i]['title_icon'] . '" title="' . $arrPart[$i]['title_icon_title'] . '" class="flag" /> ' . $ttl1;
			} elseif($arrPart[$i]['title_display'] != '') {
				$ttl1 = $arrPart[$i]['title_display'];
			}
			
			$this->_navDataText .= "\t" . $ttl1;
			$this->_navDataText .= "\t" . $arrPart[$i]['alias'];
			$this->_navDataText .= "\t" . $arrPart[$i]['accesskey'];
			$this->_navDataText .= "\t" . $arrPart[$i]['email'];
			if(strlen($arrPart[$i]['url']) > 0) {
				$this->_navDataText .= "\n" . $ftabs . $arrPart[$i]['key'] . "-URL\t" . $arrPart[$i]['url'];
			}
			if(strlen($arrPart[$i]['info_text']) > 0) {
				$this->_navDataText .= "\n" . $ftabs . $arrPart[$i]['key'] . "-Infotext\t" . $arrPart[$i]['info_text'];
			}
			$this->_navDataText .= "\n";
			
			$this->_navQuickLinkText .= $arrPart[$i]['quicklink'] == TRUE ? $arrPart[$i]['key'] . "\n" : '';
			$this->_navDisplayLinkText .= $arrPart[$i]['displaylink'] == TRUE ? $arrPart[$i]['key'] . "\n" : '';
			
			if(strlen($arrPart[$i]['url']) == 0) {
				$this->checkPath($arrPart[$i]['path']);
			}
			
			if(count($arrPart[$i]['child']) > 0) {
				$this->_buildNavDataText($arrPart[$i]['child']);
			}
			$indent--;
		}
	}
	
	public function saveJSONToFile($filePath, $bak = FALSE) {
		$this->_navDataText = '';
		$this->_navQuickLinkText = '';
		$this->_navDisplayLinkText = '';
		
		$this->_buildNavDataText($this->_arr_json['A']);
		$this->_buildNavDataText($this->_arr_json['Z']);
		$this->_buildNavDataText($this->_arr_json['S']);
		
		// backup
		if($bak) {
			// check for backup_dir
			if(!is_dir($this->_navindex_backup_dir)) {
				mkdir($this->_navindex_backup_dir, 0755, TRUE);
			}
			$backup_str = '_bak_' . date('YmdHis') . '.txt';
			$new_path = $this->_navindex_backup_dir . basename($filePath) . $backup_str;
			if(!copy($filePath, $new_path)) {
				throw new Exception('Creating backup error!');
			}
		}
				
		$old_content = file_get_contents($filePath);
//		$new_content = preg_replace(array('/^<navigation>((\n|\r\n|.)*?)<\/navigation>/m', '/^<quicklinks>((\n|\r\n|.)*?)<\/quicklinks>/m', '/^<displaylinks>((\n|\r\n|.)*?)<\/displaylinks>/m'), array('[[[navigation]]]', '[[[quicklinks]]]', '[[[displaylinks]]]'), $old_content);
		
		$new_content2 = '';
		$fh = fopen($filePath, 'r') or die("Cannot open Nav-Tree file! - $php_errormsg");
		$sec = 0;
		while(!feof($fh)) {
			$buffer = fgets($fh);
			$buffer = trim($buffer);
			// ignore blank lines
			if(strlen($buffer) == 0) {
				continue;
			}
			
			switch($buffer) {
				case '<navigation>':
					$new_content2 .= "[[[navigation]]]\n";
					$sec = 1;
					break;
				case '<quicklinks>':
					$new_content2 .= "[[[quicklinks]]]\n";
					$sec = 1;
					break;
				case '<displaylinks>':
					$new_content2 .= "[[[displaylinks]]]\n";
					$sec = 1;
					break;
				case '</navigation>':
				case '</quicklinks>':
				case '</displaylinks>':
					$sec = 0;
					break;
				default:
					if($sec == 1) {
						continue;
					} else {
						$new_content2 .= $buffer . "\n";
					}
					break;
			}
		}
		fclose($fh);
		$new_content1 = str_replace(array('[[[navigation]]]', '[[[quicklinks]]]', '[[[displaylinks]]]'), array("<navigation>\n" . $this->_navDataText . "</navigation>\n", "<quicklinks>\n" . $this->_navQuickLinkText . "</quicklinks>\n", "<displaylinks>\n" . $this->_navDisplayLinkText . "</displaylinks>\n"), $new_content2);
		
		// clean up (remove continuous newlines)
		$new_content1 = preg_replace('/^(\n|\r\n){2,}/m', '', $new_content1);
		
//		$fh = fopen($filePath, 'w+') or die("Cannot open Nav-Tree file! - $php_errormsg");
//		fwrite($fh, $new_content1);
//		fclose($fh);
		file_put_contents($filePath, $new_content1);
	}
	
	public function UpdateExistedPageLogos($logo_content_json, $dir, $filesuffix   ) {
		global $ne2_config_info;
		
	      if(!isset($filesuffix )) {
	      	$filesuffix =  $ne2_config_info['defaulthtml_filesuffix'];   	
	      }

		if(is_dir($dir)) {
			if($dh = opendir($dir)) {
				while(FALSE !== ($file = readdir($dh))) {
					// escaped dirs
//					if($file != '.' && $file != '..' && $file != 'css' && $file != 'grafiken' && $file != 'img' && $file != 'Smarty' && $file != 'ssi'  && $file != 'js'
//					                      && $file != 'vkapp' && $file != 'vkdaten' && $file != 'xampp') {
					if (!in_array($file, $ne2_config_info['nologoupdate_dir'])) { 
						if(is_dir($dir . $file . '/')) {
							// recursively
							$this->UpdateExistedPageLogos($logo_content_json, $dir . $file . '/', $filesuffix);
						} else {

							$erw = explode('.', $file);

							if((is_array($erw)) && ($erw[count($erw) - 1] == $filesuffix)) { // replace all the .shtml files
								if ($ne2_config_info['show_logoupdate_allwebpages']==1) {
									$thisfile = str_replace($_SERVER['DOCUMENT_ROOT'],'',$dir.$file);									
							 		echo("Aktualisiere  $thisfile \n");
							 	}
								$this->doReplaceLogo($logo_content_json, $dir . $file);

							}
						}
					}
				}
				closedir($dh);
			}
		}
	}
	
	public function UpdateStartPageLogo() {
		global $ne2_config_info;
		// just remove the link
		$start_page_path = $_SERVER['DOCUMENT_ROOT'] . '/'. $ne2_config_info['directoryindex_file'];
		if(file_exists($start_page_path)) {
			$fcontent = file_get_contents($start_page_path);
			$pat = '/<div id="logo">((\n|.)*?)<\/div>/i';
			preg_match($pat, $fcontent, $matches);
			$rcontent = $matches[1];
			$rcontent = str_replace(array('<a href="/">', '</a>'), '', $rcontent);
			$newContent = preg_replace($pat, '<div id="logo">' . $rcontent . '</div>', $fcontent);
			file_put_contents($start_page_path, $newContent);
		}
	}
	
	private function doReplaceLogo($logo_content_json, $page_file_path) {
		$fcontent = file_get_contents($page_file_path);
		$data = json_decode($logo_content_json);
		$pat = '/<div id="logo">(\n|.)*?<\/div>/i';
		$rcontent = '';
		$content_text = $data->content_text;
		$allow_html = $data->content_allow_html;
		if(!$allow_html) {
			$content_text = htmlentities($data->content_text, ENT_COMPAT, 'UTF-8');
		}
		if(strlen($data->content_img) < 1) {
			$rcontent = '<p><a href="/">' . $content_text . (strlen($data->content_desc) > 0 ? ' <span class="description">' . $data->content_desc . '</span>' : '') . '</a></p>';
		} else {
			$rcontent = '<a href="/">' . $data->content_img . '</a>';
			$rcontent .= '<p><a href="/">' . $content_text . (strlen($data->content_desc) > 0 ? ' <span class="description">' . $data->content_desc . '</span>' : '') . '</a></p>';
			// $rcontent .= '<a href="/">' . $data->content_img . '</a>';
		}
		$newContent = preg_replace($pat, '<div id="logo">' . $rcontent . '</div>', $fcontent);
		file_put_contents($page_file_path, $newContent);
		
		$this->replaceTitleTagContent($data->site_title_text, $page_file_path);
	}
	
	private function replaceTitleTagContent($new_title_text, $page_file_path) {
		$fcontent = file_get_contents($page_file_path);
		
		// <h1>...</h1> / get page-title
		$pattern2 = '/(<div id="titel">)((\n|.)*?)(<\/div>)/i';
		preg_match($pattern2, $fcontent, $matches);
		$page_title = $matches[2];
		$page_title = str_replace(array('<h1>', '</h1>'), '', $page_title);
		// <title>...</title>
		$pattern1 = '/<title>((\n|.)*?)<\/title>/i';
		$fcontent = preg_replace($pattern1, '<title>' . htmlentities($new_title_text, ENT_COMPAT, 'UTF-8') . ': ' . $page_title . '</title>', $fcontent);
		
		file_put_contents($page_file_path, $fcontent);
	}
	
	public function UpdateTitleFile($titile_text) {
		global $ne2_config_info;
		$titleFilePath = $ne2_config_info['app_path'] . $ne2_config_info['current_site_title_file'];
		file_put_contents($titleFilePath, htmlentities($titile_text, ENT_COMPAT, 'UTF-8'));
	}
}
?>
