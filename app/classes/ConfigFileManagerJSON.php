<?php
require_once 'ConfigFileManagerAbstract.php';
/**
 * Manager for config files with JSON file data structure
 *
 * @author Dmitry Gorelenkov
 * @internal Purpose: learning PHP -> probably low quality code, sorry :/
 */
class ConfigFileManagerJSON extends ConfigFileManager{

    /**
     * Decodes $data string to array
     * @param string $data Text to decode
     * @return array Eecoded string
     */
    public function decodeData($data) {
        if(empty($data)){
            return Array();
        }
        return json_decode($data, TRUE);
    }

    /**
     * encodes $data array to string
     * @param array $data Data to encode
     * @return string Encoded array
     */
    public function encodeData($data) {
        return json_encode($data);
    }
}

?>
