<?php

/*
 * Ajax Handler
 * purpose: handle all NavEditor ajax requests
 * Use nav_tools.js call_php() function for the php handler
 */
try {
require_once 'config.php';
require_once '../auth.php';
require_once 'classes/Input.php';

//returned data variable
$data_to_return = '';

//what file
    //path will be resolved, full path will be modified to NavEditor2/ relative path
    $file_to_call = str_replace($ne2_config_info['app_path'],'', NavTools::simpleResolvePath(Input::get_post('file')));
//what function
    $function_to_call = Input::get_post('function');
//args, data to handle
    $data_to_pass = Input::get_post('args');



//test if user has permission for the php file
    $um = new UserMgmt();
    if (!$um->isAllowAccesPHP($file_to_call)){
        throw new Exception('Kein zugriff auf "'.$file_to_call.'"');
    }


//test what file, then test what function
    switch ($file_to_call) {

        //BereichsEditor------------------------------------------------------------
        case 'app/classes/BereichsEditor.php':

            $bereichsname = $data_to_pass['bereich'];
            //falls bereich undefiniert, return error
            if (strlen($bereichsname) == 0) {
                throw new Exception('Bereich undefiniert');
            }

            require_once $ne2_config_info['app_path'].$file_to_call;

            $BerEditor = new BereichsEditor($bereichsname);
            //BereichsEditor/functions----------------------------------------------
            switch ($function_to_call) {

                case 'get_content':
                    $data_to_return = $BerEditor->get_content();
                    //falls feur tinyMCE comments ersetzen
                    if ($data_to_pass['tinymce'] == true) {
                        $data_to_return = str_replace(array('<!--#', '<!--', '-->'), array('<comment_ssi>', '<comment>', '</comment>'), $data_to_return);
                    }

                    break;


                case 'update_content':
                    $new_content = $data_to_pass['new_content'];
                    //falls feur tinyMCE comments ersetzen
                    if ($data_to_pass['tinymce'] == true) {
                        $new_content = str_replace(array('<comment_ssi>', '<comment>', '</comment>'), array('<!-' . '-#', '<!--', '-->'), $new_content);
                    }

                    $data_to_return = $BerEditor->update_content($new_content);
                    break;


                default :
                    $data_to_return = 'Wrong function name';
                    break;
            }
            break;



        //BereichsManager-----------------------------------------------------------
        case 'app/classes/BereichsManager.php':

            require_once $ne2_config_info['app_path'].$file_to_call;
            $BerManager = new BereichsManager();
            //BereichsManager/functions---------------------------------------------
            switch ($function_to_call) {
                //no need?
                case 'getAreaList':
                    $data_to_return = json_encode($BerManager->getAreaList());
                    break;

                case 'getAllAreaSettings':

                    $data_to_return = json_encode($BerManager->getAllAreaSettings());
                    break;

                case 'addAreaSettings':
                    $data_to_return = $BerManager->addAreaSettings($data_to_pass['name'], $data_to_pass['settings']);
                    break;

                case 'deleteAreaSettings':
                    $data_to_return = $BerManager->deleteAreaSettings($data_to_pass['name']);
                    break;

                case 'updateAreaSettings':
                    $data_to_return = $BerManager->updateAreaSettings($data_to_pass['name'], $data_to_pass['settings']);
                    break;
            }
            break;


        //default-------------------------------------------------------------------
        default:
            $data_to_return = 'Wrong parameters.';
            break;
    }
} catch (Exception $e) {
    $data_to_return = 'Error: ' . $e->getMessage() . "\n";
}

echo($data_to_return);
?>
