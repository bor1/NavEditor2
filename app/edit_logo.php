<?php
require_once('config.php');



require_once('../auth.php');
require_once('classes/FileHandler_Class.php');

ini_set("pcre.backtrack_limit", "200000"); // workaround for preg_replace error by large content

$fpath = $ne2_config_info['app_path'] . 'data/templates/' . $_REQUEST['template_name'];
$oper = $_REQUEST['json_oper'];
$fh = new FileHandler();

$site_title_text = '';
$titleFilePath = $ne2_config_info['app_path'] . $ne2_config_info['current_site_title_file'];
if(file_exists($titleFilePath)) {
    $site_title_text = file_get_contents($titleFilePath);
}

if($oper == 'get_content') {
    $fcontent = file_get_contents($fpath);
    $pat = '/<div id="logo">((\n|.)*?)<\/div>/i';
    preg_match($pat, $fcontent, $matches);
    $cnt = $matches[1];
    $text = '';
    $desc = '';
    $img = '';
    $img_alt = '';
    if(strpos($cnt, '<p>') !== FALSE) {
        $cnt1 = preg_replace('/<img .*?\/>/i', '', $cnt);
        $text = trim(str_replace(array('<p>', '</p>', '<a href="/">', '</a>'), '', $cnt1));
        if(strpos($text, '<span class="description">') !== FALSE) {
            $pat11 = '/<span class="description">(.*?)<\/span>/i';
            preg_match($pat11, $text, $md);
            $desc = $md[1];
            $text = preg_replace($pat11, '', $text);
        }
    }
    if(strpos($cnt, '<img') !== FALSE) {
        $pat1 = '/<img alt="([\w|\s]*?)" src="(\S+?)".*?\/>/i';
        preg_match($pat1, $cnt, $matches1);
        $img_alt = $matches1[1];
        $img = $matches1[2];
    }

    $jsn = array(
        'content_text' => $text,
        'content_desc' => $desc,
        'content_img' => $img,
        'content_img_alt' => $img_alt,
        'site_title_text' => $site_title_text
    );
    echo(json_encode($jsn));
} elseif($oper == "update_content") {
    $fcontent = file_get_contents($fpath);
    $data0 = $_REQUEST['json_content'];
    if(get_magic_quotes_gpc()) {
        $data0 = stripslashes($data0);
    }
    $data = json_decode($data0);
    $pat = '/<div id="logo">(\n|.)*?<\/div>/i';
    $rcontent = '';
    if(strlen($data->content_img) < 1) {
        $rcontent = '<p><a href="/">' . $data->content_text . (strlen($data->content_desc) > 0 ? ' <span class="description">' . $data->content_desc . '</span>' : '') . '</a></p>';
    } else {
        $rcontent .= '<a href="/">' . $data->content_img . '</a>';
        if($data->content_text == '') {
            $data->content_text = $data->site_title_text;
        }
        $rcontent .= '<p><a href="/">' . $data->content_text . (strlen($data->content_desc) > 0 ? ' <span class="description">' . $data->content_desc . '</span>' : '') . '</a></p>';
    }
    $fh->replace_div_with_id('logo', $fpath, $rcontent);
    $fh->UpdateTitleFile($data->site_title_text);
    echo('Update done!');
} elseif($oper == 'update_content_all') {
    // update template

    $fcontent = file_get_contents($fpath);
    $data0 = $_REQUEST['json_content'];
    if(get_magic_quotes_gpc()) {
        $data0 = stripslashes($data0);
    }
    $data = json_decode($data0);
    $pat = '/<div id="logo">(\n|.)*?<\/div>/i';
    $rcontent = '';
    if(strlen($data->content_img) < 1) {
        $rcontent = '<p><a href="/">' . $data->content_text . (strlen($data->content_desc) > 0 ? ' <span class="description">' . $data->content_desc . '</span>' : '') . '</a></p>';
    } else {
        $rcontent .= '<a href="/">' . $data->content_img . '</a>';
        if($data->content_text == '') {
            $data->content_text = $data->site_title_text;
        }
        $rcontent .= '<p><a href="/">' . $data->content_text . (strlen($data->content_desc) > 0 ? ' <span class="description">' . $data->content_desc . '</span>' : '') . '</a></p>';
    }
    
    $fh->replace_div_with_id('logo', $fpath, $rcontent);

    $fh->UpdateTitleFile($data->site_title_text);

    // update all
    $raw_data = $data0;
    $fh->UpdateExistedPageLogos($raw_data, $_SERVER['DOCUMENT_ROOT'] . '/',$ne2_config_info['defaulthtml_filesuffix']);
    $fh->UpdateStartPageLogo();


    echo("\nTemplate und Seiten wurden aktualisiert.\n");
}

?>
