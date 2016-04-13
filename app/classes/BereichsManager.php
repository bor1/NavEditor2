<?php
require_once 'ConfigFileManagerJSON.php';
require_once 'NavTools.php';
/**
 * Verwaltung fuer Bereiche.
 * requires config.php!
 * @uses ConfigFileManagerJSON um config datei zu verwalten
 * @uses NavTools functions
 * @author Dmitry Gorelenkov
 * @internal Purpose: learning PHP -> probably low quality code, sorry :/
 * @todo Result Class, um Ergebnis mit Warnungen/Beschreibungen/Fehler zuruckzugeben
 */
class BereichsManager {
    /**
     * Manager for config file
     * @var ConfigFileManager
     */
    private $_ConfigManager;

    /**
     * Array with all possible settings
     * @var array
     */
    private $_aPossibleSettings;

    /**
     * Array with all settings keys and empty values ''
     * @var array
     */
    private $_aEmptySettings;

    /**
     * Path to SSI files
     * @var string
     */
    private $_sPathToSSI;

    /**
     * array with possible user roles
     * @var array
     */
    private $_aUserRoles;


    /**
     * Constructor
     * @global array $ne2_config_info
     * @global array $g_bereich_settings
     */
    public function __construct() {
        global $ne2_config_info, $g_bereich_settings, $ne2_user_roles;
        $configPath = $ne2_config_info['config_file_path_bereiche'];
        $bFirstTime = false;
        //falls kein config datei vorhanden, erstellen, danach mit default values fuellen
        if(!is_file($configPath)){
            if(file_put_contents($configPath, '') === FALSE){
                throw new Exception('Cannot create new config file: '.$configPath);
            }
            $bFirstTime = true;
        }
        $this->_ConfigManager = new ConfigFileManagerJSON($configPath);
        $this->_aPossibleSettings = $g_bereich_settings['possible_bereich_settings'];
        $this->_aEmptySettings = array_fill_keys($this->_aPossibleSettings, '');
        $this->_sPathToSSI = $ne2_config_info['ssi_folder_path'];
        $this->_aUserRoles = $ne2_user_roles;

        //fall back
        if($bFirstTime){
            //falls neu erstellt, mit default values fuellen
            $this->addDefaultAreaSettings();
        }
    }

    /**
     * Search all saved Areas-names in config
     * @return array List of all Areas-names
     */
    public function getAreaList(){

        return $this->_ConfigManager->getSettingNames();

    }

    /**
     * Get array of all areas, with all settings for each area.
     * @return array associative 'area'=>'associative settings array'
     */
    public function getAllAreaSettings(){

        return $this->_ConfigManager->getSettingsArray();

    }

    /**
     * Get settings array for $sAreaName area
     * @param string $sAreaName - name of area
     * @return array associative 'setting-name'=>'setting-value'
     */
    public function getAreaSettings($sAreaName){

        return $this->_ConfigManager->getSetting($sAreaName);

    }

    /**
     * Add new area, creates empty file
     * @param string $sAreaName name of area
     * @param array $aSettings associative array of area settings
     * @return bool success
     */
    public function addAreaSettings($sAreaName, array $aSettings) {
        //settingsinhalte, verbessern
        if(!$this->_tryToMakeConsistent($aSettings)){
            throw new Exception("Cannot accept settings format!");
        }
        //filter symbols by name
        $sAreaName = NavTools::filterSymbols($sAreaName);

        //falls dateiname bei anderen einstellungen existiert, abbrechen
        $newFileName = $aSettings['file_name'];
        if(!$this->_testNewFileName($newFileName)){
            throw new Exception('Cant add, file "'. $newFileName .'" already exists');
        }

        //save settings
        $bResult = $this->_ConfigManager->addSetting($sAreaName, $aSettings);

        //falls erfolgreich neue Datei erstellen
        if ($bResult) {
            //datei mit start/end marker erstellen
            $newAreaData = NavTools::ifsetor($aSettings['content_marker_start'],'') . "\n" .
                           NavTools::ifsetor($aSettings['content_marker_end'],'');
            $this->_createSSIFile($newFileName, $newAreaData);
        }

        return $bResult;
    }

    /**
     * Remove area
     * @param string $sAreaName name of area
     * @return success
     */
    public function deleteAreaSettings($sAreaName){

        return $this->_ConfigManager->removeSetting($sAreaName);

    }

    /**
     * Update area
     * @param string $sAreaName name of area
     * @param array $aSettings associative array of area settings
     * @return success
     * @throws Exception
     * @internal TODO vllt allgemein BereichFileHandler Klass erstellen.
     */
    public function updateAreaSettings($sAreaName, array $aSettings){
        //testen ob $sAreaName existiert
        if(!in_array($sAreaName, $this->getAreaList())){
            throw new Exception("Cannot find $sAreaName in settings file to update!");
        }

        //vorherige settings temporaer speichern
        $aOldAreaSettings = $this->getAreaSettings($sAreaName);

        //settingsinhalte, verbessern
        if(!$this->_tryToMakeConsistent($aSettings)){
                throw new Exception("Cannot accept settings format!");
        }

        //will be used in next code lines...
        $sOldStartMark = $aOldAreaSettings['content_marker_start'];
        $sOldEndMark = $aOldAreaSettings['content_marker_end'];
        $sNewStartMark = $aSettings['content_marker_start'];
        $sNewEndMark = $aSettings['content_marker_end'];

        //falls file_name geaendert und nicht leer
        $sNewFileName = $aSettings['file_name'];
        if (strcasecmp($aOldAreaSettings['file_name'], $sNewFileName) != 0 && strlen($sNewFileName) > 0) {
            //test if filename is not already used in other settings
            if(!$this->_testNewFileName($sNewFileName)){
                throw new Exception("Cannot change file name, file: '$sNewFileName' already used!");
            }

            $sFileMarkers = $sOldStartMark."\n".$sOldEndMark;
            if(!$this->_createSSIFile($sNewFileName,$sFileMarkers)){
                //fehlermeldung?
            }
        }

        //pruefen ob auch umbennen noetig ist
        $newName = NavTools::ifsetor($aSettings['name']);
        //falls Name angegeben und nicht die Originalname ist
        if(strcmp($newName, $sAreaName)!=0){

            //namen zeichen filtrieren
            $newName = NavTools::filterSymbols($newName);
            $aSettings['name'] = $newName;

            //testen ob name schon vorhanden
            $allNames = $this->getAreaList();
            if(in_array($newName, $allNames)){
                throw new Exception('Can not update, name "'. $newName .'" already exists');
            }

            $this->_ConfigManager->renameSetting($sAreaName, $newName, TRUE);

            $sAreaName = $newName;
        }


        //setting speichern
        $bResult = $this->_ConfigManager->setSetting($sAreaName, $aSettings);



        // falls erfolgreich und falls marker aktualisiert werden muessen
        // alte marker in der datei ersetzen
        $bMarkMustBeChanged = strcmp($sOldStartMark,$sNewStartMark) != 0 ||
                              strcmp($sOldEndMark,$sNewEndMark) != 0;

        if($bResult && $bMarkMustBeChanged){
            $bResult &= $this->_replaceMarks($sAreaName, $sOldStartMark, $sNewStartMark);
            $bResult &= $this->_replaceMarks($sAreaName, $sOldEndMark, $sNewEndMark);
        }

        return $bResult;
    }

    /**
     * Add default areas settings to file (fallback)
     * @global array $aBereicheditors
     */
    public function addDefaultAreaSettings(){
        global $aBereicheditors, $ne2_config_info;

        $aSettings = array();

        foreach ($aBereicheditors as $values) {
            $aSettings = $values;
            $sSettingName = $aSettings['name'];
            $aSettings['content_marker_start'] = NavTools::ifsetor($ne2_config_info[$sSettingName.'_content_marker_start'],'');
            $aSettings['content_marker_end'] = NavTools::ifsetor($ne2_config_info[$sSettingName.'_content_marker_end'],'');
            $aSettings['user_role_required'] = 'user';

            $aSettingsAdjused = $this->_adjustSettingsArray($aSettings);

            $this->addAreaSettings($sSettingName, $aSettingsAdjused);
        }


    }

    /**
     * Modify $aSettings to usual format. <br/>
     * fills new fields if missing, remove fields if not allowed
     * @param array $aSettings associative with settings 'setting-name'=>'setting-value'
     * @return array Modified array
     */
    private function _adjustSettingsArray($aSettings){

        $returnArray = array_replace($this->_aEmptySettings, array_intersect_key($aSettings, $this->_aEmptySettings));

        return $returnArray;
    }

    /**
     * Prueft ob $aSettings nur geeignete einstellungen enthaelt
     * @param array $aSettings assiziatives array, der geprueft werden muss
     * @param boolean $bAllSettings [optional = FALSE] true um zu pruefen <br/>
     * ob $aSettings array auch die entsprechende groesse hat
     * @return boolean true falls konsistent
     */
    private function _testAreaSettingsConsistence($aSettings, $bAllSettings=FALSE){
        //TODO pruefen einstellungen die sein MUESSEN? und die optional sind?
        $allPossibleSettings = &$this->_aPossibleSettings;

        //falls in arg array mehr als moeglich einstellungen enthalten sind, return false
        if(count(array_diff_key($aSettings, array_flip($allPossibleSettings))) > 0){
            return false;
        }
        //falls alle settings konsistent sein muessen($bAllSettings), muessen die
        //elementenanzahlen uebereinstimmmen.
        if($bAllSettings){
            return (count($aSettings) == count($allPossibleSettings));
        }
        //sonst kann auch weniger sein, also true,
        //da alle enthalten sind (vorher ueberprueft).
        return true;
    }


    /**
     * get path of area $sAreaName
     * @param string $sAreaName
     * @return string Path to $sAreaName file
     */
    private function _getPathByAreaName($sAreaName) {
        $aAreaArray = $this->getAreaSettings($sAreaName);

        return $this->_sPathToSSI.$aAreaArray['file_name'];
    }



    /**
     * replace one text for another (marks) in file of $sAreaName
     * @param string $sAreaName
     * @param string $sOldMark
     * @param string $sNewMark
     * @return boolean Success
     */
    private function _replaceMarks($sAreaName, $sOldMark, $sNewMark) {
        $filepath = NavTools::root_filter($this->_getPathByAreaName($sAreaName));

        if(!is_file($filepath)){
            NavTools::error_log("File: '$filepath' not found");
            return FALSE;
        }

        $content = file_get_contents($filepath);
        if($content === FALSE){
            NavTools::error_log("Can not get content from '$filepath'", __METHOD__);
            return FALSE;
        }

        $pos = strpos($content,$sOldMark);
        if ($pos !== FALSE) {
            $content = substr_replace($content,$sNewMark,$pos,strlen($sOldMark));
        }

        if(file_put_contents($filepath, $content) === FALSE){
            NavTools::error_log("Can not save to '$filepath'", __METHOD__);
            return FALSE;
        }

        return TRUE;

    }

    /**
     * creates new SSI File
     * @param string $sNewFileName name of new file
     * @param string $sNewAreaData [Optional] data to store in the file
     * @return boolean Success
     */
    private function _createSSIFile($sNewFileName, $sNewAreaData = '') {
        $pathToFile = NavTools::root_filter($this->_sPathToSSI.$sNewFileName);

        if (file_exists($pathToFile) || strlen($pathToFile) == 0) {
            return FALSE;
        }

        if (!file_put_contents($pathToFile, $sNewAreaData)) {
            NavTools::error_log('Can not create area file', __METHOD__);
            return FALSE;
        }


        return TRUE;

    }

    /**
     * Tests, if new file_name doesnt used
     * @param type $sNewFileName
     * @return boolean FALSE if already in use
     */
    private function _testNewFileName($sNewFileName) {
        $aAllSettings = $this->getAllAreaSettings();
        foreach ($aAllSettings as $aSetting) {
            if(strcasecmp($aSetting['file_name'],$sNewFileName)== 0 ){
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Try to make $aSettings array consistent<br>
     * Important! file_name and help_page_name cannot be saved with path.<br>
     * Something like "/subfolder/ssi_file.shtml" is wrong!<br>
     * Only ssi_file.shtml is allowed.
     * @param array $aSettings
     * @return boolean FALSE if cannot make consistent
     */
    private function _tryToMakeConsistent(array &$aSettings) {
        //nur erlaubte einstellungen
        $aSettings = array_intersect_key($aSettings, array_flip($this->_aPossibleSettings));

        //alle settings versuchen anzupassen
        //TODO irgendeine Validator Klasse hinzufuegen bzw erstellen...
        array_walk($aSettings, function(&$item, $key) {
                    switch ($key) {
                        case 'name':
//                        case 'title':
                            $item = NavTools::filterSymbols($item);
                            break;

                        case 'help_page_name':
                        case 'file_name':
                            $aFilenameSplitted = split('\.', $item);
                            for ($i = 0; $i < count($aFilenameSplitted); $i++) {
                                $aFilenameSplitted[$i] = NavTools::filterSymbols($aFilenameSplitted[$i]);
                            }
                            $item = join('.', $aFilenameSplitted);
                            break;


                        case 'user_role_required':
                            if (!array_key_exists($item, $this->_aUserRoles)) {
//                                throw new Exception('Unknown user role: ' . $item);
                                return FALSE;
                            }
                            break;
                        default:
                            break;
                    }
                }
        );

        return $this->_testAreaSettingsConsistence($aSettings);
    }




}

?>
