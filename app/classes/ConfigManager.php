<?php
/**
 * Manager for Config files
 * requires config.php!
 * TODO implemets ConfigManagerInterface?
 */
class ConfigManager {



	private $_editor_conf_file_path; //config file to read

	private $_conf_items; //save data in Array of Array('NAME' => name, 'VALUE' => value) format
    private $_conf_items_associative; //associative Array[NAME]=VALUE


    /**
     * Constructor
     * @param string $default_conf_file config file path, to manage
     * @throws Exception
     */
	public function __construct($default_conf_file) {

		$this->_editor_conf_file_path = $default_conf_file;
        if(!is_file($this->_editor_conf_file_path)){
            throw new Exception('Config file "'.$this->_editor_conf_file_path.'" not found.');
        }
		$this->_conf_items = array();
        $this->_conf_items_associative = array();

		$this->_loadConfig();
	}

    /**
     * loads config data from file to class private array/s
     */
    private function _loadConfig(){
        $fh = fopen($this->_editor_conf_file_path, 'r') or die('Cannot open file: ' . $this->_editor_conf_file_path . '!');
        $this->_conf_items_associative = array();

        while (!feof($fh)) {

            $oline = fgets($fh);

            $pline = str_replace(array("\r", "\n", "\r\n"), '', ltrim($oline));

            if ((strlen($pline) == 0) || (substr($pline, 0, 1) == '#')) {
                continue;
            }

            $arr_opt = preg_split('/\t|\s{2,}/', $pline, 2);

            //make associative array
            $this->_conf_items_associative[$arr_opt[0]] = json_decode(isset($arr_opt[1])?$arr_opt[1]:'');
        }

        fclose($fh);
    }



    /**
     * Getter for config data array
     *
     * @param bool $bAssociative true: returns associative array, false: not associative
     * @return array Array of config data, optional not $associative
     */
	public function get_confs($bAssociative = true) {
		return ($bAssociative)?$this->_conf_items_associative:$this->_conf_items;
	}


    /**
     * Getter for only one config item
     *
     * @param type $conf_item item name
     * @param type $defaultval default value in case no config item found
     * @return string Config item value
     */
	public function get_conf_item($conf_item, $defaultval) {
        $associativeArray= &$this->_conf_items_associative;

        if (isset($associativeArray[$conf_item])){
            return $associativeArray[$conf_item];
        }

        if (isset($defaultval)) {
            return $defaultval;
        } else {
            return '';
        }
    }


    /**
     * updates $data_array settings in config file
     *
     * @param array $data_array assiciative array, settings with values to update
     * @return boolean Success
     */
    public function update_conf_items($data_array){
        $allConfsUpdated = array_merge($this->get_confs(), $data_array);

        return $this->set_conf_items($allConfsUpdated);
    }


    /**
     * sets $data_array to config file
     *
     * @param array $data_array Associative array! with ConfigItem => ConfigValue
     * @return boolean Success
     */
    public function set_conf_items($data_array) {
        if (is_null($data_array)) {
            return false;
        }

        $fpath = $this->_editor_conf_file_path;

        $newInhalt = '';
        foreach ($data_array as $key => $value) {
            $newInhalt .= $key . "\t" . json_encode($value) ."\n";
        }

        $this->_backup_conf_file();
        file_put_contents($fpath, $newInhalt);

        //After setting new params, reload config data array
        $this->_loadConfig();

        return true;
    }


    /**
     * Removes items $aItemsToRemove from config file
     *
     * @param array $aItemsToRemove array of items to remove
     * @return Success
     */
    public function remove_conf_items($aItemsToRemove){
        //first backup
        $this->_backup_conf_file();

        //create new array with values
        $removeArray = array_fill_keys($aItemsToRemove, '');
        $newArrayToSave = array_diff_key($this->_conf_items_associative,$removeArray);


        //clear config file
        file_put_contents($this->_editor_conf_file_path, '');

        //set new content
        return $this->set_conf_items($newArrayToSave);
    }

    /**
     * Makes backup of current config file
     * Once pro script runned
     */
    private function _backup_conf_file(){
        static $bDidBackup = false;
        if(!$bDidBackup){
            $fpath = $this->_editor_conf_file_path;
            copy($fpath, $fpath . '.bak');
            $bDidBackup = true;
        }


    }


}

?>