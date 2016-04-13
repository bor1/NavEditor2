<?php
//
// jQuery File Tree PHP Connector
//
// Version 1.01
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
// 24 March 2008
//
// History:
//
// 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
// 1.00 - released (24 March 2008)
//
// Output a list of files for jQuery File Tree
//

require_once('config.php');
require_once('../auth.php');
require_once('classes/FileManager.php');

$root = $ne2_config_info['upload_dir'];

$dir = urldecode($_POST['dir']);
$rechte = $_POST['rechte'];
if( file_exists($root . $dir) ) {
	$files = scandir($root . $dir);
	natcasesort($files); 
	$html = "";
	$filesinfo = array();
	$fm = new FileManager();
	if( count($files) > 2 ) { /* The 2 accounts for . and .. */
		$html .= "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
		// All dirs
		
		/*spaeter benutzen. TODO, fuer rechte usw.
		if($ne2_config_info['hide_sourceeditor'] == 0 && !$is_admin){
			$forbFolders = $ne2_config_info['important_folders'];
			$files = array_diff($files, $forbFolders );
		}*/
                
                //checkbox loading if post[checkbox] = 1;
		foreach( $files as $file ) {
			if( file_exists($root . $dir . $file) && $file != '.' && $file != '..' && is_dir($root . $dir . $file) ) {
                                $relation = htmlentities($dir . $file);
                                $chkHtml = ($rechte)? "<input class='check_box' type='checkbox' value='" . $relation . "/'>": "";
				$html .= "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . $relation . "/\">" . htmlentities($file) . "</a>" . $chkHtml . "</li>";
			}
		}
		// All files
                $number =0;
		foreach( $files as $file ) {
			if( file_exists($root . $dir . $file) && $file != '.' && $file != '..' && !is_dir($root . $dir . $file) ) {
				$ext = preg_replace('/^.*\./', '', $file);
				$relation = htmlentities($dir . $file); 
                                $chkHtml = ($rechte)? "<input class='check_box' type='checkbox' value='" . $relation . "'>": "";
                                $origfile = $dir . $file;
				$mainpath = $_SERVER['DOCUMENT_ROOT'];
				$html .= "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . $relation . "\">" . htmlentities($file) . "</a>" . $chkHtml . "</li>";
				//datei info (array) zu dem $filesinfo array hinzufuegen
                                $number=$number+1;
                                $filesinfo[$number] = $fm->getFileInfo($mainpath. $origfile);
                                
                                
                             //   $filesinfo[$relation]['file_name'] = htmlentities($filesinfo[$relation]['file_name']);
			}
		}
		$html .= "</ul>";
                //special fuer rechte
                $html = (($rechte && $dir == "/") ? "<ul class='jqueryFileTree'><li class='directory expanded'><p rel='/' href='#'>Alle Seiten</p><input class='check_box' type='checkbox' value='/'>" : "" ). $html;
	}
	$json_data = array(
		'html' => $html,
		'filesinfo' => $filesinfo
	);
     //   $json_data=array_utf8_encode_recursive($json_data);
//	 $json =preg_replace_callback('/\\\u(\w\w\w\w)/',
//         function($matches)  {  return '&#'.hexdec($matches[1]).';';    }
//        , json_encode($json_data));
//        echo $json;
        
        $json_data=array_utf8_encode_recursive($json_data);
      echo json_encode($json_data);
	 // echo json_encode($json_data);
}

function array_utf8_encode_recursive($dat)
        { if (is_string($dat)) {
            return utf8_encode($dat);
          }
          if (is_object($dat)) {
            $ovs= get_object_vars($dat);
            $new=$dat;
            foreach ($ovs as $k =>$v)    {
                $new->$k=array_utf8_encode_recursive($new->$k);
            }
            return $new;
          }
         
          if (!is_array($dat)) return $dat;
          $ret = array();
          foreach($dat as $i=>$d) $ret[$i] = array_utf8_encode_recursive($d);
          return $ret;
        } 
?>