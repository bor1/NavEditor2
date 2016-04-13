<?php
//require_once 'config_users.php';


$aBereicheditors = Array(
    'kurz_editor' => Array(
        'name' => 'kurzinfo',
        'edit_file_path' => 'app/edit_kurz.php',
        'help_page_name' => 'kurz_editor',
        'title' => 'Kurzinfo',
        'description' => 'Linke Spalte',
        'file_name' => 'kurzinfo.shtml'
    ),
    'inhalts_editor' => Array(
        'name' => 'inhaltsinfo',
        'edit_file_path' => 'app/edit_inhalts.php',
        'help_page_name' => 'inhalts_editor',
        'title' => 'Inhaltsinfo',
        'description' => '&Uuml;ber dem Haupttext, aber unterhalb der &Uuml;berschrift auf jeder Seite',
        'file_name' => 'inhaltsinfo.shtml'
    ),
    'fuss_editor' => Array(
        'name' => 'fusstext',
        'edit_file_path' => 'app/edit_fuss.php',
        'help_page_name' => 'fuss_editor',
        'title' => 'Fu&szlig;text',
        'description' => 'Optionaler Bereich unter dem Text',
        'file_name' => 'footerinfos.shtml'

    ),
    'zielgrp_menu' => Array(//zielgruppenmenu
        'name' => 'zielgrpmenue',
        'edit_file_path' => 'app/edit_zielgrpmenu.php',
        'help_page_name' => 'zielgrpmenu_editor',
        'title' => 'Zielgruppenmen&uuml;',
        'description' => 'Zielgruppemmenu und Umgebung (Kopfteil der Seite)',
        'file_name' => 'zielgruppenmenu.shtml'
    ),
    'zusatzt_editor' => Array(
        'name' => 'zusatzinfo',
        'edit_file_path' => 'app/edit_zusatz.php',
        'help_page_name' => 'zusatz_editor',
        'title' => 'Zusatzinfo',
        'description' => 'Rechte Spalte oder am Seitenende',
        'file_name' => 'zusatzinfo.shtml'
    ),
    'sidebar_editor' => Array(
        'name' => 'sidebar',
        'edit_file_path' => 'app/edit_sidebar.php',
        'help_page_name' => 'sidebar_editor',
        'title' => 'Sidebar',
        'description' => 'Sidebar bearbeiten x',
        'file_name' => 'sidebar.shtml'
    )
);




//user roles associative array
//Array('admin'=>'Administrator','user'=>'Benutzer'...);
//!! config_users.php muss vorher gelanden werden!
foreach($ne2_user_roles as $roleName=>$roleData){
    $g_bereich_settings['user_roles_key_value'][$roleName] = $roleData['name'];
}


//bereich settings mit typ usw..
//TODO typ mit variablen bestimmen. z.B.  $ne2SettingTypMemo, $ne2SettingTypText
$g_bereich_settings['bereich_settings'] = Array(
    'name' => Array('type'=>'text', 'name'=>'Name', 'notempty'=>true),
    'title' => Array('type'=>'text', 'name'=>'Titel', 'notempty'=>true),
    'file_name' => Array('type'=>'text', 'name'=>'Dateiname', 'notempty'=>true),
    'description' => Array('type'=>'text', 'name'=>'Beschreibung'),
//    'content_before' => Array('type'=>'memo', 'name'=>'Inhalt vor dem Start Marker'),
    'content_marker_start' => Array('type'=>'text', 'name'=>'Start Marker'),
    'content_marker_end' => Array('type'=>'text', 'name'=>'End Marker'),
//    'content_after' => Array('type'=>'memo', 'name'=>'Inhalt nach dem End Marker'),
    'help_page_name' => Array('type'=>'text', 'name'=>'HelpPage Name'),
    'user_role_required' => Array('type'=>'values','values'=>$g_bereich_settings['user_roles_key_value'], 'name'=>'Minimum Rolle', 'notempty'=>true)
);

//moegliche einstellungen fuer bereiche
$g_bereich_settings['possible_bereich_settings'] = array_keys($g_bereich_settings['bereich_settings']);



?>
