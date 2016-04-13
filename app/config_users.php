<?php
/**
 * Config for users..
 *
 */

//possible User settings/fields
$ne2_user_params = Array(
    'user_name'     => Array(
        'name'      => 'Benutzername',
        'editable'  => true
    ),
    'password_hash' => Array(
        'name'      => 'Passwort',
        'editable'  => true
    ),
    'vorname'       => Array(
        'name'      => 'Vorname',
        'editable'  => true
    ),
    'nachname'      => Array(
        'name'      => 'Nachname',
        'editable'  => true
    ),
    'email'         => Array(
        'name'      => 'E-Mail',
        'editable'  => true
    ),
    'rolle'         => Array(
        'name'      => 'Rolle',
        'editable'  => true
    ),
    'permission'    => Array(
        'name'      => 'Rechte',
        'editable'  => true
    ),
    'ablaufdatum'   => Array(
        'name'      => 'Ablaufdatum',
        'editable'  => true
    ),
    'erstellungsdatum' => Array(
        'name'      => 'Erstellungsdatum',
        'editable'  => false
    ),
    'letzter_login' => Array(
        'name'      => 'Letzter Login',
        'editable'  => false
    ),
    'bedienermodus' => Array(
        'name'      => 'Bedienermodus',
        'editable'  => true
    ),
    'zusatzrechte'  => Array(
        'name'      => 'Zusatzrechte',
        'editable'  => true,
        'allow'         => Array(
            'name'      => 'Erlaubte Seiten',
            'editable'  => true
        ),
        'deny'          => Array(
            'name'      => 'Nicht Erlaubte Seiten',
            'editable'  => true
        ),
        'specialrights' => Array(
            'name'      => 'Spezielle Rechte',
            'editable'  => true
        )
    )
);

//user params simple.
function get_ne2_user_params_simple() {
    static $ne2_user_params_simple;
    if (!is_array($ne2_user_params_simple)){
        $ne2_user_params_simple = Array();
        foreach ($GLOBALS['ne2_user_params'] as $key => $val) {
            $ne2_user_params_simple[$key] = '';
        }
    }
    return $ne2_user_params_simple;
}

//get not editable user params
function get_ne2_user_params_not_editable() {
    static $ne2_user_params_not_editable;
    if (!is_array($ne2_user_params_not_editable)){
        $ne2_user_params_not_editable = Array();
        foreach ($GLOBALS['ne2_user_params'] as $key => $val) {
            if ($val['editable'] === false) {
                $ne2_user_params_not_editable[$key] = '';
            }
        }
    }
    return $ne2_user_params_not_editable;
}


$ne2_user_roles = Array(
  'user' => Array(
    'value' => 100,
    'name' => 'Benutzer'
  ),
  'redaktor' => Array(
    'value' => 200,
    'name' => 'Redakteur'
  ),
  'admin' => Array(
    'value' => 1000,
    'name' => 'Administrator'
  )
);

$ne2_user_modus= Array(
  'normal' => Array(
    'value' => 0,
    'name' => 'Normalmodus'
  ),
  'expert' => Array(
    'value' => 1,
    'name' => 'Expertenmodus'
  )
);

//default rights to access files (path relative to NavEditor2 folder). If not listed => allow access for everyone
//TODO save in extra php file, and require everytime? because of require_ONCE by auth.php ... works only for caller php file.
$ne2_default_file_rights = Array(
//    'app/do_upload.php' => 'user',
    'nav_editor.php' => 'redaktor',//test
    'conf_editor.php' => 'admin',
//    'design_editor.php' => 'admin',
    'update.php' => 'admin',
    'user_manager.php' => 'admin',
    'app/create_conf.php' => 'admin',
//    'app/edit_design.php' => 'admin',
    'app/live_update.php' => 'admin',
);
?>
