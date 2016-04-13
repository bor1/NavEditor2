<?php
require_once 'ConfigFileManagerJSON.php';
require_once 'NavTools.php';

/**
 * Default editor fuer Bereiche.
 *
 * @uses config.php some global variables
 * @uses NavTools.php some functions
 * @author Dmitry Gorelenkov
 * @internal Purpose: learning PHP -> probably low quality code, sorry :/
 *
 */
class BereichsEditor {

    private $_fpath; //file path
    private $_areaname; //areaname
    private $_content_splitted; //splitted (start, middle, end) array. By markers
    private $_conf_array; //array mit daten aus config datei
    private $_start_marker; //startmarker
    private $_end_marker; //endmarker
    private $_content_start; //content inhalt bevor start marker
    private $_content_middle; //content inhalt zwischen start und end markers
    private $_content_end; //content inhalt nach dem end marker

    /**
     * Constructor
     *
     * @global array $ne2_config_info
     * @param string $areaname Name des Bereichs

     */
    public function __construct($areaname) {
        global $ne2_config_info;
        $this->_areaname = $areaname;
        $config_file_path = $ne2_config_info['config_file_path_bereiche'];
        $confMngr = new ConfigFileManagerJSON($config_file_path);
        $this->_conf_array = $confMngr->getSetting($areaname);
        $this->_start_marker = \NavTools::ifsetor($this->_conf_array[$ne2_config_info['content_marker_start_setting']]);
        $this->_end_marker = \NavTools::ifsetor($this->_conf_array[$ne2_config_info['content_marker_end_setting']]);
        $filename_setting = $ne2_config_info['bereich_filename_setting']; //einfach file_name ?
        $filename = \NavTools::ifsetor($this->_conf_array[$filename_setting]);
        if (strlen($filename) == 0) {
            throw new Exception('No filename setting: "'.$filename_setting.'" in config: "'.$config_file_path.'" found');
        }
        $this->_fpath = $ne2_config_info['ssi_folder_path'] . $filename;

        if (!is_file($this->_fpath)) {
//            throw new Exception('File ('.$this->_fpath.') not found');
            file_put_contents($this->_fpath, '');
        }
        $this->_loadContents();
    }


    /**
     * Loads content from file or $data to local variables
     * @param String $data [Optional] String to parse. Otherwise will be loaded from file
     */
    private function _loadContents($data = null){
        $content_full = (is_null($data))?file_get_contents($this->_fpath):$data;
        $this->_content_splitted = $this->_get_splitted_content($content_full, $this->_start_marker, $this->_end_marker);
        $this->_content_start = $this->_content_splitted['start'];
        $this->_content_middle = $this->_content_splitted['middle'];
        $this->_content_end = $this->_content_splitted['end'];
    }


    /**
     * Gets content.
     * @return String Content from file.
     */
    public function get_content() {
        //_loadContents() ? +stabilitaet -performance
        return $this->_content_middle;
    }

    /**
     * Updates content
     * @param String $newContent Content to put
     * @return String message about succes
     */
    public function update_content($newContent) {
        if (get_magic_quotes_gpc()) {
            $data = stripslashes($newContent);
        } else {
            $data = $newContent;
        }

        // just add start_content, start marker, end_content, end marker...
        $data = $this->_content_start . $this->_start_marker . "\n"
                        . $data . "\n"
                        . $this->_end_marker . $this->_content_end;

        file_put_contents($this->_fpath, $data);

        //refresh local variables, for the case you call get_content() after update_content(), have to get updated data.
        $this->_loadContents($data);


        return NavTools::ifsetor($this->_conf_array['title'], "Bereich") . ' wurde aktualisiert';
    }

    /**
     * Gibt gesplitterte nach markers daten zurueck.
     * <p>eigentliche content_markers sind nicht enthalten!</p>
     * @param String $content content zu bearbeiten
     * @param String $start_mark start content marker
     * @param String $end_mark end content marker
     * @return Array Array with 3 elements: 'start' -> start content, 'middle' -> middle content, 'end' -> end content
     *
     */
    private function _get_splitted_content($content, $start_mark, $end_mark) {
        //TODO regex, versuchen normal, einschaetzen ob was fehlt,
        //falls ja, versuchen fallback, wieder einschaetzen, vergleichen wo mehr gefunden wurde


        $returnArray = array();
        $start_content = "";
        $end_content = "";
        //falls startmarker definiert bzw. not empty
        if (strlen($start_mark) > 0) {

            $start_pos = strpos($content, $start_mark);

            //TEMP. falls nicht gefunden, mit fallBack versuchen
            if ($start_pos === FALSE) {
                $start_mark = $this->_tryFallBack_start_mark($content, $this->_areaname);
                if($start_mark !== FALSE){
                    $start_pos = strpos($content, $start_mark);
                }
            }

            //falls position bestimmt, trennen, start_content speichern
            if ($start_pos !== FALSE) {
                $start_content = (string) substr($content, 0, $start_pos);
                //content weiter abgeschnitten benutzen
                $content = substr($content, $start_pos + strlen($start_mark));
            }
        }

        //end position bestimmen
        if (strlen($end_mark) > 0) {
            $end_pos = strpos($content, $end_mark);

            //TEMP. falls nicht gefunden, mit fallBack versuchen
            if ($end_pos === FALSE) {
                $end_mark = $this->_tryFallBack_end_mark($content, $this->_areaname);
                if($end_mark !== FALSE){
                    $end_pos = strpos($content, $end_mark);
                }
            }

            //falls position bestimmt, trennen, end_content speichern
            if ($end_pos !== FALSE) {
                $end_content = (string) substr($content, $end_pos + strlen($end_mark));
                //content endlich bestimmen
                $content = substr($content, 0, $end_pos);
            }
        }




        //alle contents gesplittert speichern und zurueckgeben
        $returnArray['start'] = $start_content;
        $returnArray['middle'] = $content;
        $returnArray['end'] = $end_content;

        return $returnArray;
    }




    //================FALLBACK FUNCS============================================
    //temp fallBack getDataArray
    private function _getFallBackData($bereich) {
        $data = Array(
            'kurzinfo' => Array(
                'startMarks' => Array('<div id="kurzinfo">',
                                      '<div id="kurzinfo">  <!-- begin: kurzinfo -->'),
                'startRegex' => '/^\s*<div id="kurzinfo"\s*>\s*/',
                'endMarks' => Array('</div>  <!-- end: kurzinfo -->'),
                'endRegex' => '/<\/div>\s*(<!--\s* end:\s* kurzinfo\s* -->|)[\s\n]*$/'
            ),
            'inhaltsinfo' => Array(
                'startMarks' => Array('<div id="inhaltsinfo">',
                                      '<div id="inhaltsinfo">  <!-- begin: inhaltsinfo -->'),
                'startRegex' => '/^\s*<div\s* id="inhaltsinfo"\s*>\s*/',
                'endMarks' => Array('</div>  <!-- end: inhaltsinfo -->'),
                'endRegex' => '/<\/div>\s*(<!--\s* end:\s* inhaltsinfo\s* -->|)[\s\n]*$/'
            ),
            'fusstext' => Array(
                'startMarks' => Array(Array('<!-- /footerinfos -->')),
                'startRegex' => '',
                'endMarks' => Array('<!-- /footerinfos -->'),
                'endRegex' => '',
            ),
            'sidebar' => Array(
                'startMarks' => Array('<div id="sidebar" class="noprint">',
                                      '<aside><div id="sidebar" class="noprint">  <!-- begin: sidebar -->',
                                      '<aside><div id="sidebar" class="noprint">  <!-- begin: sidebar -->'),
                'startRegex' => '/^\s*<div\s* id="sidebar"\s*>\s*/',
                'endMarks' => Array('</div></aside>  <!-- end: sidebar -->'),
                'endRegex' => '/<\/div>\s*(<!--\s* end: sidebar\s* -->|)\s*$/'
            ),
            'zielgrpmenue' => Array(
                'startMarks' => Array('<h2 class="skip"><a name="hauptmenumarke" id="hauptmenumarke">Zielgruppennavigation</a></h2>'),
                'startRegex' => '',
                'endMarks' => Array(''),
                'endRegex' => ''
            ),
            'zusatzinfo' => Array(
                'startMarks' => Array('<div id="zusatzinfo" class="noprint">',
                    '<div id="zusatzinfo" class="noprint">  <!-- begin: zusatzinfo -->',
                    '<a id="zusatzinfomarke" name="zusatzinfomarke"></a>'),
                'startRegex' => '/^\s*<div\s* id="zusatzinfo"\s*>\s*/',
                'endMarks' => Array('</div>  <!-- end: zusatzinfo -->'),
                'endRegex' => '/<\/div>\s*(<!--\s* end: zusatzinfo\s* -->|)\s*$/'
            ),
        );

        return NavTools::ifsetor($data[$bereich],Array());
    }

    //try to find position with mark or regex
    private function _tryToFindFallbackMark(&$content, array &$marksArray, $regex) {
        $mark = FALSE;

        //try simple marks
        foreach ($marksArray as $mark) {
            if (strlen($mark) > 0) {
                $pos = strpos($content, $mark);
                if ($pos !== FALSE) {
                    return $mark;
                }
            }
        }

        //try with regex
        if(strlen($regex) > 0) {
            $matches = Array();
            $result = preg_match ($regex, $content, $matches, PREG_OFFSET_CAPTURE);
            if($result > 0){
                $mark = $matches[0][0];
            }
        }

        return $mark;
    }

    //temp fallBack start marker
    private function _tryFallBack_start_mark(&$content, $bereich) {
        $data = $this->_getFallBackData($bereich);
        if(empty($data)){return false;}

        $marksArray = $data['startMarks'];
        $regex = $data['startRegex'];
        return $this->_tryToFindFallbackMark($content,$marksArray,$regex);

    }

    //temp fallBack end marker
    private function _tryFallBack_end_mark(&$content, $bereich) {
        $data = $this->_getFallBackData($bereich);
        if(empty($data)){return false;}

        $marksArray = $data['endMarks'];
        $regex = $data['endRegex'];
        return $this->_tryToFindFallbackMark($content,$marksArray,$regex);
    }

}

?>
