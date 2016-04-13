<?php
require_once('classes/NavTools.php');
require_once ('config_users.php');
require_once('config_bereichseditor.php');
// error_reporting(E_ALL & ~E_STRICT);
// ini_set('display_errors', 'on');



//in case of added trailing slash by DOCUMENT_ROOT //no need?
if(substr($_SERVER['DOCUMENT_ROOT'],-1)=='/'){
    $_SERVER['DOCUMENT_ROOT'] = substr($_SERVER['DOCUMENT_ROOT'], 0, -1);
}

// the path of NavEditor2, by default: $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/tools/NavEditor2/'
// please include trailing slash!
$ne2_config_info['app_path_without_host'] = '/vkdaten/tools/NavEditor2/';
$ne2_config_info['app_path'] = $_SERVER['DOCUMENT_ROOT'] .$ne2_config_info['app_path_without_host'];
$ne2_config_info['log_path'] = $_SERVER['DOCUMENT_ROOT'] .$ne2_config_info['app_path_without_host'] . "log/";
$ne2_config_info['cgi-bin_path'] = NavTools::simpleResolvePath($_SERVER['DOCUMENT_ROOT'] . "/../cgi-bin/");

//error log file path
$ne2_config_info['error_log_file'] = $ne2_config_info['log_path'].'errors.log';

// the filename of user-data
$ne2_config_info['user_data_file_name'] = '.hteditoruser';

// the filename of debug times
$ne2_config_info['debug_execution_file_name'] = 'debug-execution-time.log';
$ne2_config_info['debug_execution_file'] = $ne2_config_info['log_path'].$ne2_config_info['debug_execution_file_name'];
$ne2_config_info['debug_time']  = 1;

// where to save uploaded data (without trailing slash!)
$ne2_config_info['upload_dir'] = $_SERVER['DOCUMENT_ROOT'] . '';



// current public title with html
$ne2_config_info['app_title'] = 'NavEditor 2 <sup>Delta</sup>';

// current public title with html
$ne2_config_info['app_titleplain'] = 'NavEditor 2 Delta';

// current version
$ne2_config_info['version'] = '2.13.0618';

// update host
$ne2_config_info['update_url'] = 'http://www.vorlagen.uni-erlangen.de/downloads/naveditor/';



// path to naveditor config files
$ne2_config_info['config_path']     = $ne2_config_info['app_path'] . 'data/';

// path for template files
$ne2_config_info['template_path']   = $ne2_config_info['app_path'] . 'data/templates/';

// template files for default  pages
$ne2_config_info['template_default'] = 'seitenvorlage.html';

// JS folder name in vkdaten folder
$ne2_config_info['js_folder_name']   = 'js';

// CSS folder name in vkdaten folder
$ne2_config_info['css_folder_name']   = 'css';

//URL to naveditor
$ne2_config_info['ne2_url']   = "http://".$_SERVER['HTTP_HOST'].$ne2_config_info['app_path_without_host'];

//ssi folder path
$ne2_config_info['ssi_folder_path'] = $_SERVER['DOCUMENT_ROOT']. "/ssi/";


// new in editor editable conf-items
require_once('classes/ConfigManager.php');
//NavTools::save_execution_time("Reading Config", $ne2_config_info['debug_execution_file'], $ne2_config_info['debug_time']);

$default_conf_file = $ne2_config_info['config_path'] . 'ne2_config.conf';
if(!file_exists($default_conf_file)) {
	$fallback_file = $ne2_config_info['config_path'] . '_ne2_config.conf';
	@copy($fallback_file, $default_conf_file);
}
$config_manager = new ConfigManager($default_conf_file);

$ne2_config_info['custom_content_css_classes']          = $config_manager->get_conf_item('custom_content_css_classes', 'clear|unsichtbar|marker|hinweis|links|rechts|marker|bildrechts|bildlinks|vollbox|klein_box_links|klein_box_rechts|box_rechts|box_links');
$ne2_config_info['show_navtree_numbers'] 		= $config_manager->get_conf_item('show_navtree_numbers', 0); // 1 or 0
$ne2_config_info['navtree_start_open'] 			= $config_manager->get_conf_item('navtree_start_open', 0);
$ne2_config_info['session_timeout'] 			= $config_manager->get_conf_item('session_timeout', 7200 );
// js_keep_session_time sollte kleiner sein als session_timeout:
$ne2_config_info['js_keep_session_time'] 		= $config_manager->get_conf_item('js_keep_session_time', 1200 );

// Sicherung, dass die Session-Times nicht zu klein ausfallen:
if ($ne2_config_info['session_timeout'] < 1800) {
	$ne2_config_info['session_timeout'] = 1800;
}
if ($ne2_config_info['js_keep_session_time'] > $ne2_config_info['session_timeout']) {
	$ne2_config_info['js_keep_session_time'] = 600;
}

$ne2_config_info['dashboard_feed'] 			= $config_manager->get_conf_item('dashboard_feed', 'http://blogs.fau.de/webworking/feed');
$ne2_config_info['hide_sourceeditor'] 			= $config_manager->get_conf_item('hide_sourceeditor', 1);
$ne2_config_info['max_upload_filesize'] 		= $config_manager->get_conf_item('max_upload_filesize', 50); // megabytes
$ne2_config_info['show_logoupdate_allwebpages'] 	= $config_manager->get_conf_item('show_logoupdate_allwebpages', 1);
 $ne2_config_info['defaulthtml_filesuffix']  		= $config_manager->get_conf_item('defaulthtml_filesuffix', 'shtml');
 // helpfilesuffix:
 $ne2_config_info['help_filesuffix']  			= $config_manager->get_conf_item('help_filesuffix', '.html');
 // path for help files
$ne2_config_info['help_path']                   = $config_manager->get_conf_item('help_path', $ne2_config_info['app_path'] .'data/helps/');   ;
// path for temp files
$ne2_config_info['temp_path']                   = $config_manager->get_conf_item('temp_path',$ne2_config_info['app_path'] . '_tmp/');
 //einige .conf dateinamen.
 $ne2_config_info['website']                    = $config_manager->get_conf_item('website', 'website.conf');
 $ne2_config_info['variables']                  = $config_manager->get_conf_item('variables', 'variables.conf');
 // indexdatei:
  $ne2_config_info['directoryindex_file']  		= $config_manager->get_conf_item('directoryindex_file', 'index.shtml');
// where to store backup files
 $ne2_config_info['backup_root']                = $config_manager->get_conf_item('backup_root', $ne2_config_info['app_path'] . '.htbackup/');
 // backup type: 1-Only one (with .bak-suffix); 2-Many (with Timestamp-suffix)
 $ne2_config_info['backup_type']                = $config_manager->get_conf_item('backup_type', 2);
// where to backup navgationsindex.txt
 $ne2_config_info['navindex_backup_dir']  		= $config_manager->get_conf_item('navindex_backup_dir', $_SERVER['DOCUMENT_ROOT'] . '/vkdaten/navindex_backup/');

// Logfile fuer Logins
 $ne2_config_info['file_loginlog']  			= $config_manager->get_conf_item('file_loginlog', 'pwreset.log');
// Check Login Sperre (in Sekunden)
 $ne2_config_info['timeout_loghistory']  		= $config_manager->get_conf_item('timeout_loghistory', '3600');

 //webauftritt configfiles ordner
 $ne2_config_info['usual_configs_path']         = $_SERVER['DOCUMENT_ROOT'] . "/vkdaten/";

//config file path fuer bereiche
$ne2_config_info['config_file_path_bereiche']   = $ne2_config_info['config_path'] . 'bereiche.conf';

// Optionen fuer Funktionen

// activate_univis_mitarbeitereditor:  Wird der Editor fuer UnivIS-Extra Personendaten angezeigt? (Alter Editor war er default an, ab 2012 besser default aus)
$ne2_config_info['tool_univis_mitarbeitereditor'] 	= $config_manager->get_conf_item('tool_univis_mitarbeitereditor', 0); // 1 or 0


if ($ne2_config_info['tool_univis_mitarbeitereditor'] ) {
	$ne2_config_info['activate_toolmenu']  =1;
}

$ne2_config_info['page_content_marker_start']           = $config_manager->get_conf_item('page_content_marker_start',  '<!-- TEXT AB HIER -->');
 $ne2_config_info['page_content_marker_end']            = $config_manager->get_conf_item('page_content_marker_end', '<!-- AB HIER KEIN TEXT MEHR -->');
 $ne2_config_info['page_content_marker_start_fallback'] = $config_manager->get_conf_item('page_content_marker_start_fallback',  '<a name="contentmarke" id="contentmarke"></a>');
 $ne2_config_info['page_content_marker_end_fallback']  	= $config_manager->get_conf_item('page_content_marker_end_fallback', '<hr id="vorfooter" />');
 $ne2_config_info['page_content_marker_preinhaltsinfo'] = $config_manager->get_conf_item('page_content_marker_preinhaltsinfo',  '<!--#include virtual="/ssi/inhaltsinfo.shtml" -->');

// Definition der Seitenbereiche
 $ne2_config_info['content_marker_start_setting']        = 'content_marker_start';
 $ne2_config_info['content_marker_end_setting']          = 'content_marker_end';
 $ne2_config_info['bereich_filename_setting']            = 'file_name';


//========================= LOESCHEN SPAETER BEGIN =============================
 $ne2_config_info['zusatzinfo_file']                    = $config_manager->get_conf_item('zusatzinfo_file', '/ssi/zusatzinfo.shtml');
// Marker fuer Zusatzinfo:
 $ne2_config_info['zusatzinfo_content_marker_start']  	= $config_manager->get_conf_item('zusatzinfo_content_marker_start',  '<div id="zusatzinfo" class="noprint">  <!-- begin: zusatzinfo -->');
 $ne2_config_info['zusatzinfo_content_marker_startdiv'] = $config_manager->get_conf_item('zusatzinfo_content_marker_startdiv', '<a id="zusatzinfomarke" name="zusatzinfomarke"></a>');
 $ne2_config_info['zusatzinfo_content_marker_end']  	= $config_manager->get_conf_item('zusatzinfo_content_marker_end', '</div>  <!-- end: zusatzinfo -->');
   // Kurzinfo Datei
 $ne2_config_info['kurzinfo_file']                      = $config_manager->get_conf_item('kurzinfo_file', '/ssi/kurzinfo.shtml');
// Marker fuer Kurzinfo:
 $ne2_config_info['kurzinfo_content_marker_start']  	= $config_manager->get_conf_item('kurzinfo_content_marker_start',  '<div id="kurzinfo">  <!-- begin: kurzinfo -->');
 $ne2_config_info['kurzinfo_content_marker_end']        = $config_manager->get_conf_item('kurzinfo_content_marker_end', '</div>  <!-- end: kurzinfo -->');
//TMP sidebar
 $ne2_config_info['sidebar_content_marker_start']   	= $config_manager->get_conf_item('sidebar_content_marker_start',  '<aside><div id="sidebar" class="noprint">  <!-- begin: sidebar -->');
 $ne2_config_info['sidebar_content_marker_startdiv']    = $config_manager->get_conf_item('sidebar_content_marker_startdiv', '<aside><div id="sidebar" class="noprint">  <!-- begin: sidebar -->');
 $ne2_config_info['sidebar_content_marker_end']         = $config_manager->get_conf_item('sidebar_content_marker_end', '</div></aside>  <!-- end: sidebar -->');//verkehrt?
 $ne2_config_info['sidebar_file']                       = $config_manager->get_conf_item('sidebar_file', '/ssi/sidebar.shtml');

   // Inhaltsinfo Datei
 $ne2_config_info['inhaltsinfo_file']                   = $config_manager->get_conf_item('inhaltsinfo_file', '/ssi/inhaltsinfo.shtml');
// Marker fuer Inhaltsinfo:
 $ne2_config_info['inhaltsinfo_content_marker_start']  	= $config_manager->get_conf_item('inhaltsinfo_content_marker_start',  '<div id="inhaltsinfo">  <!-- begin: inhaltsinfo -->');
 $ne2_config_info['inhaltsinfo_content_marker_end']  	= $config_manager->get_conf_item('inhaltsinfo_content_marker_end', '</div>  <!-- end: inhaltsinfo -->');


   // Footerinfo Datei
 $ne2_config_info['footerinfo_file']                    = $config_manager->get_conf_item('footerinfo_file', '/ssi/footerinfos.shtml');
// Marker fuer Footerinfo sind nicht vorhanden, da diese ausserhalb der Datei definiert wurden

   // Zielgruppenmenu Datei
 $ne2_config_info['zielgruppenmenu_file']               = $config_manager->get_conf_item('zielgruppenmenu_file', '/ssi/zielgruppenmenu.shtml');
// Marker fuer das Zielgruppenmenu sind nicht vorhanden, da diese ausserhalb der Datei definiert wurden


 //========================= LOESCHEN SPAETER END ==============================




$ne2_config_info['live_update_backupfiles'] = array(
	".hteditoruser",
	"ne2_config.conf",
	"htacc_template_auth",
	"htacc_template_host",
	"current_design.txt",
	"templates/seitenvorlage.html",
	"heads/kopf-z0s0.shtml",
	"heads/kopf-z1s0.shtml",
	"heads/kopf-z0s1.shtml",
	"heads/kopf-z1s1.shtml",
);


//wichtige verzeichnisse, die in manchen Faellen nicht bearbeitet werden duerfen
$ne2_config_info['important_folders'] = Array('css','grafiken','img','ssi','js','vkdaten','univis','vkapp');
// Verzeichnisse in denen bei einer Aktualisierung aller Dateien fuer den Kopfteil nicht geschaut wird
$ne2_config_info['nologoupdate_dir'] = Array('.', '..', 'css','grafiken','img','ssi','js','vkdaten','univis','vkapp','Smarty','xampp');


// jqueryFileTree Icons Pfad
$ne2_config_info['jquery_file_tree'] = Array (
    'icons' => Array(
        'newfolder_icon' => $ne2_config_info['ne2_url'] . $ne2_config_info['css_folder_name'].'/images/newfolder.png',
        'rename_icon' => $ne2_config_info['ne2_url'] . $ne2_config_info['css_folder_name'].'/images/rename.png',
        'delete_icon' => $ne2_config_info['ne2_url'] . $ne2_config_info['css_folder_name'].'/images/delete.png',
        'create_new_icon' => $ne2_config_info['ne2_url'] . $ne2_config_info['css_folder_name'].'/images/newdocument.png',
    ),
    'colors' => Array(
        'color_notallow' => '#999',
        'color_someallow' => '#666'
    )
);

// Menu
 $ne2_menu = array(
	1 => array(
		'id' 	=> 1,
		'title' => 'Dashboard',
		'link'	=> 'dashboard.php',
		'role'	=> 'user',
		'sub'	=> 0,
		'up'	=> 0,
		'desc'	=> '',
	),
	10 => array(
		'id'		=> 10,
		'title'	=> 'Bearbeiten',
		'link'	=> '#',
		'role'	=> 'user',
		'sub'	=> 1,
		'up'	=> 0,
		'desc'	=> 'Seiten und Struktur erstellen und &auml;ndern',
	),
	11 => array(
		'id'		=> 11,
		'title'	=> 'Seite und Navigation',
		'link'	=> 'nav_editor.php',
		'role'	=> 'user',
		'sub'	=> 0,
		'up'	=> 10,
		'desc'	=> '',
	),
	12 => array(
		'id'		=> 12,
		'title'	=> 'Bilder und Dateien',
		'link'	=> 'file_editor.php',
		'role'	=> 'redaktor',
		'sub'	=> 0,
		'up'	=> 10,
		'desc'	=> '',
	),
	20 => array(
		'id'		=> 20,
		'title'	=> 'Allgemeine Bereiche',
		'link'	=> '#',
		'role'	=> 'redaktor',
		'sub'	=> 1,
		'up'	=> 0,
		'desc'	=> 'Allgemeine Information von jeder Seiten bearbeiten',
	),
//	21...3x loaded from config_bereichseditor

	40 => array(
		'id'		=> 40,
		'title'	=> 'Tools',
		'link'	=> '#',
		'role'	=> 'redaktor',
		'sub'	=> 1,
		'up'	=> 0,
		'desc'	=> 'Funktionen die modulare Werkzeuge des Webbaukastens betreffen',
	),
	41 => array(
		'id'		=> 41,
		'title'	=> 'UnivIS-Integration: Mitarbeiter',
		'link'	=> 'ma_editor.php',
		'role'	=> 'user',
		'sub'	=> 0,
		'up'	=> 40,
		'desc'	=> 'Zielgruppemmenu und Umgebung (Kopfteil der Seite)',
	),
    42 => array(
		'id'		=> 41,
		'title'	=> 'Caches',
		'link'	=> 'remove_caches.php',
		'role'	=> 'admin',
		'sub'	=> 0,
		'up'	=> 40,
		'desc'	=> 'Caches l&ouml;eschen',
	),

	50 => array(
		'id'		=> 50,
		'title'	=> 'Erweitert',
		'link'	=> '#',
		'role'	=> 'admin',
		'sub'	=> 1,
		'up'	=> 0,
		'desc'	=> 'Administratorfunktionen',
		'addclass'	=> 'role_admin',
	),
	51 => array(
		'id'		=> 51,
		'title'	=> 'Daten zur Website',
		'link'	=> 'website_editor.php',
		'role'	=> 'admin',
		'sub'	=> 0,
		'up'	=> 50,
		'desc'	=> '',
	),
	52 => array(
		'id'		=> 52,
		'title'	=> 'Konfiguration',
		'link'	=> 'conf_editor.php',
		'role'	=> 'admin',
		'sub'	=> 0,
		'up'	=> 50,
		'desc'	=> '',
	),

	53 => array(
		'id'		=> 53,
		'title'	=> 'Design',
		'link'	=> 'design_editor.php',
		'role'	=> 'admin',
		'sub'	=> 0,
		'up'	=> 50,
		'desc'	=> '',
	),
	54 => array(
		'id'		=> 54,
		'title'	=> 'Benutzerverwaltung',
		'link'	=> 'user_manager.php',
		'role'	=> 'admin',
		'sub'	=> 0,
		'up'	=> 50,
		'desc'  => '',
    ),
    55 => array(
        'id' => 55,
        'title' => 'Bereiche verwalten',
        'link'  => 'bereiche_manager.php',
        'role'  => 'admin',
        'sub'   => 0,
        'up'    => 50,
        'desc'  => 'Bereiche Verwalten',
    ),
	56 => array(
		'id'		=> 56,
		'title'	=> 'Update',
		'link'	=> 'update.php',
		'role'	=> 'admin',
		'sub'	=> 0,
		'up'	=> 50,
		'desc'	=> '',
	),
	60 => array(
		'id'		=> 60,
		'title'	=> 'Hilfe',
		'link'	=> '#',
		'role'	=> 'public',
		'sub'	=> 1,
		'up'	=> 0,
		'desc'	=> '',
	),
	61 => array(
		'id'		=> 61,
		'title'	=> 'Nutzung der Hilfe',
		'link'	=> 'help_using.php',
		'role'	=> 'public',
		'sub'	=> 0,
		'up'	=> 60,
		'desc'	=> '',
	),
	62 => array(
		'id'		=> 62,
		'title'	=> 'Detaillierte Hilfe',
		'link'	=> 'help_details.php',
		'role'	=> 'public',
		'sub'	=> 0,
		'up'	=> 60,
		'desc'	=> '',
	),
	63 => array(
        'id'		=> 63,
		'title'	=> 'Spezielle Fragen &amp; Antworten',
		'link'	=> 'help_special_faq.php',
		'role'	=> 'public',
		'sub'	=> 0,
		'up'	=> 60,
		'desc'	=> '',
	),
	64 => array(
        'id'		=> 64,
		'title'	=> 'Forum &amp; Blog',
		'link'	=> 'help_forum_blog.php',
		'role'	=> 'public',
		'sub'	=> 0,
		'up'	=> 60,
		'desc'	=> '',
	),
	65 => array(
        'id'		=> 65,
		'title'	=> 'Nutzungslizenz',
		'link'	=> 'licence.php',
		'role'	=> 'public',
		'sub'	=> 0,
		'up'	=> 60,
		'desc'	=> '',
	),
	66 => array(
        'id'		=> 66,
		'title'	=> 'Entwickler',
		'link'	=> 'credits.php',
		'role'	=> 'public',
		'sub'	=> 0,
		'up'	=> 60,
		'desc'	=> '',
	),
	100 => array(
		'id'		=> 100,
		'title'	=> 'Abmelden',
		'link'	=> 'logout.php',
		'role'	=> 'user',
		'sub'	=> 0,
		'up'	=> 0,
		'desc'	=> '',
		'addclass'	=> 'logout',
		'attribut' => 'onclick="javascript:return confirm(\'Wollen Sie sich wirklich abmelden?\');"',
	)
 );


 // Diese Apps duerfen auch aufgerufen werden, wenn man nicht angemeldet ist
$ne2_config_info['nologin_file'] = array();
foreach($ne2_menu as $value) {
    if(strcasecmp($value['role'], 'public') == 0){
        array_push($ne2_config_info['nologin_file'], $value['link']);
    }
}




//dynamisch bereichseditors binden
//MUST BE SET BEFORE:
//          $ne2_config_info['config_file_path_bereiche'],
//          $ne2_config_info[{bereich}.'_content_marker_start']
//          $ne2_config_info[{bereich}.'_content_marker_end']

require_once ('classes/BereichsManager.php');

 $BerManager = new BereichsManager();
 $alleBereiche = $BerManager->getAllAreaSettings();

 foreach ($alleBereiche as $aBereich) {
    static $i = 21;

    $ne2_menu[$i] = array(
        'id' => $i,
        'title' => $aBereich['title'],
        'link' => 'default_editor.php?' . $aBereich['name'],
        'role' => $aBereich['user_role_required'],
        'sub' => 0,
        'up' => 20,
        'desc' => $aBereich['description']
    );

    $i++;
}




$ne2_config_info['symbols_being_replaced'] = array ('ä', 'ö', 'ü', 'ß', "Ä", "Ö", "Ü","ẞ");
$ne2_config_info['symbols_replacement'] = array ('ae', 'oe', 'ue', 'ss', "AE", "OE", "UE", "SS");
$ne2_config_info['regex_removed_symbols'] = '/[^a-zA-Z0-9\-_\s]/';


//default html includes for every file
$ne2_config_info['default_includes_js_css'] = Array(
    "styles.css?".date('Ymdis'), "jquery-1.7.2.min.js", "loading.js"
);


//NavTools::save_execution_time("Config Initialised", $ne2_config_info['debug_execution_file'], $ne2_config_info['debug_time']);

// no cache!
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past




?>
