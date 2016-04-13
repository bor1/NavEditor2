<?php
require_once('config.php');
require_once('../auth.php');

$path = $_SERVER['DOCUMENT_ROOT'] . '/ssi';

function add_to_retval($fname) {
    global $retval;
    $arr1 = array(
        'value' => $fname,
        'text' => $fname
    );
    array_push($retval['designs'], $arr1);
}

function getDescriptionFromMetafile($patameter, $metafilepfad){
        if (file_exists($metafilepfad)){
    $metaContent = file_get_contents($metafilepfad);
    if (preg_match('/'.$patameter.'\.css[\s]*(.*)/', $metaContent, $regs)) {
        $ret = $patameter.'.css - '.$regs[1];
    }else{
        $ret = $patameter.'.css - Keine Beschreibung gefunden!';
    }
    return utf8_encode($ret);
        }
}

$oper = $_REQUEST['oper'];
$retval = array(
    'current_design' => '',
    'designs' => array()
);
switch($oper){
case 'get_file_list':
    if(is_dir($path)) {
        if($dh = opendir($path)) {
            while(($file = readdir($dh)) !== false) {
                if(substr($file, 0, 5) == 'head-') {
                    add_to_retval($file);
                }
            }
        }
        closedir($dh);
    }

    $curr_fpath = $ne2_config_info['app_path'] . 'data/current_design.txt';
    $curr_fname = 'head-d3.shtml'; // default design
    if(file_exists($curr_fpath)) {
        $curr_fname = file_get_contents($curr_fpath);
    }

    $retval['current_design'] = $curr_fname;

    echo(json_encode($retval));
            break;
case 'set_head_file':
    $old_fname = $path . '/head.shtml';
    $new_fname = $path . '/' . $_REQUEST['new_head_file'];

    unlink($old_fname);
    copy($new_fname, $old_fname);
    chmod($old_fname, 0755);

    $curr_fpath = $ne2_config_info['app_path'] . 'data/current_design.txt';
    file_put_contents($curr_fpath, $_REQUEST['new_head_file']);

    echo('Update done!');
            break;
case 'get_screenshot':
    $design_file_name = $_REQUEST['head_file_name'];
    $thumb_dir_name = str_replace(array('head-', '.shtml'), '', $design_file_name);
    $thumb_path = $_SERVER['DOCUMENT_ROOT'] . '/css/' . $thumb_dir_name . '/thumb.png';
    if(file_exists($thumb_path)) {
        echo('<img alt="" src="/css/' . $thumb_dir_name . '/thumb.png" border="0" />');
    } else {
        echo('Keine Screenshots vorhanden.');
    }
            break;
case 'get_settings':
    $design_file_name = $_REQUEST['head_file_name'];
    $css_settings_dir_name = str_replace(array('head-', '.shtml'), '', $design_file_name);
    $css_settings_file = $_SERVER['DOCUMENT_ROOT'] . '/css/'.$css_settings_dir_name.'/layout.css';
    $meta_file = $_SERVER['DOCUMENT_ROOT'] . '/css/'.$css_settings_dir_name.'/meta.txt';
            if (!file_exists($css_settings_file)){
                echo(json_encode(false));
                break;
            }
    $layout_css = file_get_contents($css_settings_file);
    $settings = array();
    preg_match_all('%(/\*)?@import url\(.*/(basemod_.*)\.css\);(\*/)?%', $layout_css, $result, PREG_SET_ORDER);
    for ($matchi = 0; $matchi < count($result); $matchi++) {
        $settings[$matchi]['setting'] = $result[$matchi][2];
        $description = getDescriptionFromMetafile($result[$matchi][2], $meta_file);
        $settings[$matchi]['setting_descr'] = $description;

        if($result[$matchi][1] == "" &&  $result[$matchi][3] == "" ){
            //aktiviert, nicht kommentiert.
            $settings[$matchi]['checked'] = TRUE;
        }else{
            //deaktiviert, kommentiert.
            $settings[$matchi]['checked'] = FALSE;
        }
    }
    echo(json_encode($settings));
    break;
case 'set_settings':
    $design_file_name = $_REQUEST['head_file_name'];
    $css_settings_dir_name = str_replace(array('head-', '.shtml'), '', $design_file_name);
    $css_settings_file = $_SERVER['DOCUMENT_ROOT'] . '/css/'.$css_settings_dir_name.'/layout.css';
    $layout_css = file_get_contents($css_settings_file);
    $layout_settings = json_decode($_POST['settings'], TRUE);
    foreach($layout_settings as $setting_line => $checked){
        if($checked === TRUE){
            $commentB = '';
            $commentE = '';
        }else{
            $commentB = '/*';
            $commentE = '*/';
        }
        $layout_css = preg_replace('%(/\*)?(@import url\(.*/'.$setting_line.'\.css\);)(\*/)?%', $commentB.'$2'.$commentE, $layout_css);
    }
    $fp = fopen($css_settings_file, "w") or die('Cannot open file!');
    fputs($fp, $layout_css);
    fclose ($fp);
    echo("Design ".$css_settings_dir_name." aktualisiert!");
}

?>