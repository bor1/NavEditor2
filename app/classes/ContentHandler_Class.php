<?php
// require config.php!
class ContentHandler {
	private $_defaultPath;
	private $_filePath;
	private $_templateHtml;
	private $_title;
	private $_content;
	private $_newTemplateHtml;
	private $_siteTitle;
	private $_backupManager;
	private $_content_block_pattern;
	private $_content_block_pattern_fallback; 
	private $_content_marker_start;
	private $_content_marker_end;
	private $_content_marker_start_fallback; 
	private $_content_marker_end_fallback;	
	private $_content_marker_preinhaltsinfo;
        
	function __construct($str_path) {
		global $ne2_config_info;
		
		$website_conf = $this->getConfValues($ne2_config_info['website']);
		$this->_siteTitle = $website_conf['titel-des-Webauftritts'];
		if($this->_siteTitle != '') {
			$this->_siteTitle .= ': ';
		}
		$this->_defaultPath = $ne2_config_info['app_path'] . 'data/templates/seitenvorlage.html';
		if(!file_exists($this->_defaultPath)) {
			$this->_defaultPath = $ne2_config_info['app_path'] . 'data/templates/_seitenvorlage.html'; // fail-safe
		}
		$this->_newTemplateHtml = '';
		
		$this->_content_block_pattern  = '/('.$ne2_config_info['page_content_marker_start'].')((\r|\n|\r\n|.)*?)('.$ne2_config_info['page_content_marker_end'].')/i';
		
		$this->_content_block_pattern_fallback  = '/(<a name="contentmarke" id="contentmarke"><\/a>)((\r|\n|\r\n|.)*?)(<hr id="vorfooter" \/>)/i';
		$this->_content_marker_start =  $ne2_config_info['page_content_marker_start']; //   || '<a name="contentmarke" id="contentmarke"></a>';
		$this->_content_marker_end =  $ne2_config_info['page_content_marker_end']; //  || '<hr id="vorfooter" />';
		// fuer alte Templateversionen: 
		$this->_content_marker_start_fallback  =  $ne2_config_info['page_content_marker_start_fallback']; 
		$this->_content_marker_end_fallback =  $ne2_config_info['page_content_marker_end_fallback']; 
				
                $this->_content_marker_preinhaltsinfo = $ne2_config_info['page_content_marker_preinhaltsinfo'];
                
		$this->_filePath = $str_path;
		if(file_exists($this->_filePath)) {
//			if(($this->_templateHtml = file_get_contents($this->_filePath)) == FALSE) {
			if(($this->_templateHtml = $this->file_get_contents_utf8($this->_filePath)) == FALSE) {
				throw new Exception('Unable to open file: ' . $this->_filePath);
			}
		} else { // try default path
			if(($this->_templateHtml = file_get_contents($this->_defaultPath)) == FALSE) {
				throw new Exception('Unable to open file: ' . $this->_filePath);
			}
		}
	}
	
	private function file_get_contents_utf8($fn) {
		$content = file_get_contents($fn);
		$content = str_ireplace('<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />', $content);
		return mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, 'UTF-8, ISO-8859-1', TRUE));
	}
	
	public function setBackupManager($backupManager) {
		$this->_backupManager = $backupManager;
	}
	
	public function setContents($newTitle, $newContent) {
		$this->_title = htmlentities($newTitle, ENT_COMPAT, 'UTF-8');
		$this->_content = $newContent;
		if($this->_content == '') {
			$str_pattern = $this->_content_block_pattern;
			preg_match($str_pattern, $this->_templateHtml, $matches);
			$this->_content = trim($matches[2]);
		}
	}
	
	private function replaceTitles() {
		// <title>...</title>
		$pattern1 = '/<title>((\n|.)*?)<\/title>/i';
		$this->_templateHtml = preg_replace($pattern1, '<title>' . $this->_siteTitle . $this->_title . '</title>', $this->_templateHtml);
		
		// <h1>...</h1>
		$pattern2 = '/(<div id="titel">)((\n|.)*?)(<\/div>)/i';
		$this->_templateHtml = preg_replace($pattern2, '$1<h1>' . $this->_title . '</h1>$4', $this->_templateHtml);
	}
	private function replaceLogo() {
		// <div id="logo">...</div>
		global $ne2_config_info;
		$pattern = '%<div id="logo">.*?</div>%s';
		$logoValues = $this->getConfValues($ne2_config_info['website']);
		
		If ($this->_filePath != $_SERVER['DOCUMENT_ROOT'].'/index.shtml'){
			$hrefB = '<a href="/">';
			$hrefE = '</a>';
		}
		If ($logoValues['kurzbeschreibung-zum-Webauftritt'] != ""){
			$description = '<span class="description">'.$logoValues['kurzbeschreibung-zum-Webauftritt'].'</span>';
		}
		If ($logoValues['logo-URL'] != ""){
			If($logoValues['logo-Alt'] != ""){$logoAlt = ' alt="'.$logoValues['logo-Alt'].'"';}
			If($logoValues['logo-Width'] != ""){$logoW = ' width="'.$logoValues['logo-Width'].'"';}
			If($logoValues['logo-Height'] != ""){$logoH = ' height="'.$logoValues['logo-Height'].'"';}
			$logoImg = '<img'.$logoAlt.' src="'.$logoValues['logo-URL'].'"'.$logoW.$logoH.' border="0" />';
		}
		$namewebauftritt = "";
		if ($logoValues['name-des-Webauftritts'] != "") {
			$namewebauftritt = '<p>'.$hrefB.$logoValues['name-des-Webauftritts'].$hrefE.$description.'</p>';
		} else {
			if ($logoValues['kurzbeschreibung-zum-Webauftritt'] != ""){
				 $namewebauftritt = '<p>'.$description.'</p>';
			}
		}
		

		$logo = '<div id="logo">'.$hrefB.$logoImg.$hrefE.$namewebauftritt.'</div>';
		$this->_templateHtml = preg_replace($pattern, $logo, $this->_templateHtml);
	}
	
	private function getConfValues($confFile) {
	$fpath = $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/'.$confFile;
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
				$opt1 = array($arr_opts1[0], str_replace(" \\", "", $arr_opts1[1]));
				continue;
			} else {
				$opt1[1] .= " " . str_replace(" \\", "", $pline);
			}
		} else {
			if($to_concat) {
				$opt1[1] .= " " . $pline; // the last line
				$retv[$opt1[0]] = $opt1[1];
				//array_push($retv, $opt1);
				$to_concat = FALSE;
			} else {
				$arr_opts = preg_split('/\t|\s{2,}/', $pline);
				/*$opt = array(
					'opt_name' => $arr_opts[0],
					'opt_value' => $arr_opts[1]
				);
				array_push($retv, $opt); */
				$retv[$arr_opts[0]] = $arr_opts[1];
			}
		}
	}
	fclose($fh);
	return $retv;
	}
	
	private function replaceContent() {
	
		$str_pattern = $this->_content_block_pattern;
		preg_match($str_pattern, $this->_templateHtml, $matches);
		// error		
		if(count($matches) < 3) {
			$str_pattern = $this->_content_block_pattern_fallback;
		
		}

		// $str_pattern = $this->_content_block_pattern;
		$this->_templateHtml = preg_replace($str_pattern, '$1' . "\n" . $this->_content . "\n" . '$4', $this->_templateHtml);
	}
	
	private function _replaceContentEx() {
		$start_pos = mb_strpos($this->_templateHtml, $this->_content_marker_start);
                $fallbackstart = mb_strpos($this->_templateHtml, $this->_content_marker_start_fallback );
                $fallbackstart += mb_strlen($this->_content_marker_start_fallback );
                $is_newstartpos = 0;
		if ($start_pos !== FALSE) {
			$start_pos += mb_strlen($this->_content_marker_start);
                        $is_newstartpos = 1;
		} else {
			$start_pos = mb_strpos($this->_templateHtml, $this->_content_marker_start_fallback );
			if ($start_pos !== FALSE) {
				$start_pos += mb_strlen($this->_content_marker_start_fallback );
			}
		}		
		
		$end_pos = mb_strpos($this->_templateHtml, $this->_content_marker_end);
                $no_defaultendmarker = 0;
		if ($end_pos === FALSE) {
			$end_pos = mb_strpos($this->_templateHtml, $this->_content_marker_end_fallback ); 
                        $no_defaultendmarker = 1;
		}
		
	
		if($start_pos === FALSE || $end_pos === FALSE) {
			return;
		}
                
		$old_content = mb_substr($this->_templateHtml, $start_pos, $end_pos - $start_pos);
                $textlen= mb_strlen($old_content);
                if ((($textlen < 1) && ($is_newstartpos==1)) || ($is_newstartpos==0)) {
                    //  use fallback
                  // $real_content = mb_substr($this->_templateHtml, $fallbackstart, $end_pos - $fallbackstart);                                         
                   $part_head = mb_substr($this->_templateHtml, 0, $fallbackstart);
                   $part_head .=  "\n\t\t\t";
                   $part_head .= $this->_content_marker_preinhaltsinfo;
                   $part_head .= "\n\n\t\t\t";
                   $part_head .= $this->_content_marker_start;                   
               //    $end_pos -= mb_strlen($this->_content_marker_start); 
                } else {
                   
                   $part_head = mb_substr($this->_templateHtml, 0, $start_pos);                    
                }
                
                
		
		$part_tail = mb_substr($this->_templateHtml, $end_pos);
                if ($no_defaultendmarker==1) {
                    $part_tail = $this->_content_marker_end.$part_tail;                    
                }
		$this->_templateHtml = $part_head . "\n". $this->_content. "\n" . $part_tail;
	}
	
	// Do the replacement
	public function ReplaceContents($backup = TRUE) {
//		$this->replaceTitles();
//		$this->_replaceContentEx();
		
		if($backup) {
			$this->_backupManager->backup($this->_filePath);
		}
		$this->SaveAsDraft();
//		file_put_contents($this->_filePath, $this->_templateHtml);
		$draft_path = $this->_filePath . '.buffer';
		if(file_exists($draft_path)) {
			copy($draft_path, $this->_filePath);
			@unlink($draft_path);
		}
	}
	
	public function SaveAsDraft() {
		$draft_path = $this->_filePath . '.buffer';
		if(file_exists($draft_path)) {
			$this->_templateHtml = $this->file_get_contents_utf8($draft_path);
		}
		
		$this->replaceTitles();
		$this->replaceLogo();
		$this->_replaceContentEx();
		
		file_put_contents($draft_path, $this->_templateHtml);
	}
	
	public function GetContentHtml() {
		$pattern = $this->_content_block_pattern;
		preg_match($pattern, $this->_templateHtml, $matches);
		// error
		
		if(count($matches) < 3) {
			$pattern = $this->_content_block_pattern_fallback;
			preg_match($pattern, $this->_templateHtml, $matches);						
			if(count($matches) < 3) {
				return '<span style="color:red;">INFO: No content tags were found (' . count($matches) . '), this page is possibly not produced from templates.</span>';
			}
		}
		$ret = $matches[2];
		return $ret;
	}
	
	public function getContentHtmlEx() {
		$start_pos = mb_strpos($this->_templateHtml, $this->_content_marker_start);
		$end_pos = mb_strpos($this->_templateHtml, $this->_content_marker_end);
		$is_newstartpos = 0;
		$fallbackstart;
               
		$start_pos = mb_strpos($this->_templateHtml, $this->_content_marker_start);
                $fallbackstart = mb_strpos($this->_templateHtml, $this->_content_marker_start_fallback );
                $fallbackstart += mb_strlen($this->_content_marker_start_fallback );
                
		if ($start_pos !== FALSE) {
			$start_pos += mb_strlen($this->_content_marker_start);
                        $is_newstartpos = 1;
		} else {
			$start_pos = mb_strpos($this->_templateHtml, $this->_content_marker_start_fallback );
			if ($start_pos !== FALSE) {
				$start_pos += mb_strlen($this->_content_marker_start_fallback );
			}
                                                                                           
		}		
		
		$end_pos = mb_strpos($this->_templateHtml, $this->_content_marker_end);
		if ($end_pos === FALSE) {
			$end_pos = mb_strpos($this->_templateHtml, $this->_content_marker_end_fallback ); 
		}        

		if($start_pos === FALSE || $end_pos === FALSE) {
			return '<span style="color:red; font-size: 1.5em; font-weight: bold;">Der Textbereich der Seite konnte nicht ermittelt werden. <br />M&ouml;glicherweise wurden fehlerhafte HTML-Anweisungen eingef&uuml;gt, die das Analysieren des Textes verhindern. Bitte korrigieren Sie die fehlerhaften Bestandteile des Codes &uuml;ber den Quellcode-Editor bei dem Men&uuml;punkt Bilder und Dateien (f&uuml;r Administratoren) oder nutzen einen HTML-Editor.  </span>';
		}
		$len = mb_strlen($this->_templateHtml);
		$real_content = mb_substr($this->_templateHtml, $start_pos, $end_pos - $start_pos);
                $textlen= mb_strlen($real_content);
                if (($textlen < 1) && ($is_newstartpos)) {
                    //  use fallback
                    $end_pos -= mb_strlen($this->_content_marker_start);  
                    $pos_inhaltsinfo = mb_strpos($this->_templateHtml, $this->_content_marker_preinhaltsinfo );
                    $real_content = mb_substr($this->_templateHtml, $fallbackstart, $end_pos - $fallbackstart); 
                       if (($pos_inhaltsinfo !== FALSE) && ($pos_inhaltsinfo>$fallbackstart)) {        
                           $real_content = preg_replace($this->_content_marker_preinhaltsinfo,'',$real_content);
                           $real_content = preg_replace('/<>/','',$real_content);
                       }                        
                    // remove other trash
                    $real_content = preg_replace('/<!-- Inhaltsinfo (\*+) -->/','',$real_content);    
                    $real_content = preg_replace('/<!-- (\*+) -->/','',$real_content); 
                }
                $real_content = trim($real_content,"\n");
                
		return $real_content;
	}
	
	public function change_ssi_head($new_head_filename) {
		// get content in <head>
		$start_pos = mb_strpos($this->_templateHtml, '<head>');
		$end_pos = mb_strpos($this->_templateHtml, '</head>');
		$start_pos += mb_strlen('<head>');
		$head_content = trim(mb_substr($this->_templateHtml, $start_pos, $end_pos - $start_pos));
		$head_content_array = explode("\n", $head_content);
		$new_head_content = '';
		foreach($head_content_array as $line) {
			if(mb_strpos($line, '<!--#include virtual') !== FALSE) {
				$new_head_content .= '<!--#include virtual="/ssi/' . $new_head_filename . '" -->' . "\n";
			} else {
				$new_head_content .= $line . "\n";
			}
		}
		
		$part_head = mb_substr($this->_templateHtml, 0, $start_pos);
		$part_tail = mb_substr($this->_templateHtml, $end_pos);
		$this->_templateHtml = $part_head . "\n" . $new_head_content . $part_tail;
		
		$draft_path = $this->_filePath . '.buffer';
		file_put_contents($draft_path, $this->_templateHtml);
	}
	
	private function pcre_error_deocde() {
		$r = '';
		switch(preg_last_error()) {
		case PREG_NO_ERROR:
			$r = "pcre_error: PREG_NO_ERROR!\n";
			break;
		case PREG_INTERNAL_ERROR:
			$r = "pcre_error: PREG_INTERNAL_ERROR!\n";
			break;
		case PREG_BACKTRACK_LIMIT_ERROR:
			$r = "pcre_error: PREG_BACKTRACK_LIMIT_ERROR!\n";
			break;
		case PREG_RECURSION_LIMIT_ERROR:
			$r = "pcre_error: PREG_RECURSION_LIMIT_ERROR!\n";
			break;
		case PREG_BAD_UTF8_ERROR:
			$r = "pcre_error: PREG_BAD_UTF8_ERROR!\n";
			break;
		case PREG_BAD_UTF8_OFFSET_ERROR:
			$r = "pcre_error: PREG_BAD_UTF8_OFFSET_ERROR!\n";
			break;
		}
		return $r;
	}
}
?>
