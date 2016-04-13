<?php

class FileManager {

    public function __construct() {
        
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir")
                        $this->rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            return rmdir($dir);
        }
    }

    public function createSubFolder($ppath, $fname) {
        $compl_name = $ppath . '/' . $fname;
        if (!is_dir($compl_name)) {
            return mkdir($compl_name);
        } else {
            return 'Der Ordner existiert bereits';
        }
    }

    public function createNewFile($ppath, $fname, $ext) {
        $compl_name = $ppath . '/' . $fname . '.' . $ext;
        if (!file_exists($compl_name)) {
            $retVal = fopen($compl_name, "w") or die("can't open file");
            if ($retVal) {
                fclose($retVal);
            }
        } else {
            $retVal = 'Die Datei existiert bereits';
        }
        return $retVal;
    }

    public function loadFolderTree($path, $is_root = TRUE) {
        $da = array(
            'dir_name' => basename($path),
            'dir_path' => $path,
            'sub' => array()
        );
        if ($is_root) {
            $dl = array_diff(scandir($path), array('.', '..', 'js', 'Smarty', 'univis', 'vkapp')); // here insert excluded folders
        } else {
            $dl = array_diff(scandir($path), array('.', '..', 'NavEditor2')); // here insert excluded folders
        }
        foreach ($dl as $di) {
            if (is_dir($path . '/' . $di)) {
                array_push($da['sub'], $this->loadFolderTree($path . '/' . $di, FALSE));
            }
        }
        return $da;
    }

    private function build_file_icon($file_name) {
        $fa = explode('.', $file_name);
        $ext = $fa[count($fa) - 1];
        $icon_path = $_SERVER['DOCUMENT_ROOT'] . '/img/links/';
        if (file_exists($icon_path . $ext . '.gif')) {
            return "<img alt='$ext' src='/img/links/$ext.gif' border='0' width='16' height='16' />";
        } else {
            return "<img alt='' src='/img/links/extern.gif' border='0' width='16' height='16' />";
        }
    }

    public function getFileList($path) {
        $fl = array();

        $dl = array_diff(scandir($path), array('.', '..'));
        foreach ($dl as $di) {
            $di = $path . '/' . $di;
            if ((is_file($di)) && (substr(basename($di), 0, 6) != 'thumb_')) {
                $f = array(
                    'icon' => '',
                    'file_name' => '',
                    'extension' => '',
                    'file_size' => '',
                    'access_time' => ''
                );
                $ap = pathinfo($di);
                $f['icon'] = $this->build_file_icon($ap['basename']);
                $f['file_name'] = $ap['basename'];
                $f['extension'] = $ap['extension'];
                $f['file_size'] = $this->formatBytes(filesize($di));
                $f['access_time'] = date("d.m.Y H:i:s", filemtime($di));
                array_push($fl, $f);
            }
        }
        return $fl;
    }

    public function getFileInfo($fpath) {
        $editable_exts = array('txt', 'html', 'shtml', 'css', 'js', 'pl', 'conf', 'php', 'htaccess');
        $r = array(
            'file_name' => '',
            'file_size' => '',
            'modified_time' => '',
            'thumb_name' => '',
            'url' => '',
            'editable' => FALSE
        );
        if (file_exists($fpath)) {
            $pi = pathinfo_utf($fpath);
            $r['file_name'] = $pi['basename'];
            $r['file_size'] = filesize($fpath);
            $r['modified_time'] = date("d.m.Y H:i:s", filemtime($fpath));
            $thumb_path = substr($fpath, 0, strrpos($fpath, '/')) . '/thumb_' . $pi['basename'];
            if (file_exists($thumb_path)) {
                $r['thumb_name'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $thumb_path);
            }
            $r['url'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $fpath);
            $ext = $pi['extension'];
            if (in_array(strtolower($ext), $editable_exts)) {
                $r['editable'] = TRUE;
            }
        }
        return $r;
    }

    private function thumbPath($file_path) {
        $file_name_only = basename($file_path);
        $thumb_name = 'thumb_' . $file_name_only;
        $thumb_path = str_replace($file_name_only, $thumb_name, $file_path);
        return $thumb_path;
    }

    public function deleteFile($file_path) {
        // if thumb image exists, also delete
        $thumb_path = $this->thumbPath($file_path);
        if (file_exists($thumb_path)) {
            @unlink($thumb_path);
        }
        @unlink($file_path);
    }

    public function deleteFolder($folder_path) {
        return $this->rrmdir($folder_path); //ddd
    }

    public function renameFile($file_path, $new_name) {
        $info = pathinfo($file_path);
        $path = $info['dirname'];

        if (is_dir($file_path)) {
            return rename($file_path, $path . '/' . $new_name);
        } else {
            //$file_name =  basename($file,'.'.$info['extension']);
            $thumb_path = $this->thumbPath($file_path);
            if (file_exists($thumb_path)) {
                @rename($thumb_path, $path . '/thumb_' . $new_name . '.' . $info['extension']);
            }
            return rename($file_path, $path . '/' . $new_name . '.' . $info['extension']);
        }
    }

    public function getFileContent($file_path) {
        if (file_exists($file_path)) {
            $c = file_get_contents($file_path);
            // replace ssi tags
            $c = str_replace(array('<!--#', '<!--', '-->'), array('<comment_ssi>', '<comment>', '</comment>'), $c);
            return $c;
        } else {
            return $file_path;
        }
    }

    public function setFileContent($file_path, $content) {
        if (file_exists($file_path)) {
            if (get_magic_quotes_gpc()) {
                $content = stripslashes($content);
            }
            $content = str_replace(array('<comment_ssi>', '<comment>', '</comment>'), array('<!-' . '-#', '<!--', '-->'), $content);
            file_put_contents($file_path, $content);
        }
    }

    public function backupCurrentConfigFile($file_path) {
        if (file_exists($file_path)) {
            $bak_file_path = $file_path . '-' . date('Ymd');
            return copy($file_path, $bak_file_path);
        }
    }

    public function restoreCurrentConfigFile($file_path) {
        $bak_file_path = $file_path . '-' . date('Ymd');
        if (!file_exists($bak_file_path)) {
            return;
        }
        if (file_exists($file_path)) {
            if (filesize($file_path) == filesize($bak_file_path)) {
                // Gleiche Datei, brauche nichts zu tun				
                return;
            } else {
                // Neue Datei, also neue Konfigwerte
                // Sichere diese extra ab in eine der defaultfiles und ueberschreibe dann mit
                // vorheriger Datei
                $file_name_only = basename($file_path);
                $default_name = '_' . $file_name_only;
                $default_file_path = str_replace($file_name_only, $default_name, $file_path);
                copy($file_path, $default_file_path);
                return copy($bak_file_path, $file_path);
            }
        } else {
            return copy($bak_file_path, $file_path);
        }
    }

}

function pathinfo_utf($path) {
    if (strpos($path, '/') !== false)
        $basename = end(explode('/', $path));
    elseif (strpos($path, '\\') !== false)
        $basename = end(explode('\\', $path));
    else
        return false;
    if (empty($basename))
        return false;

    $dirname = substr($path, 0, strlen($path) - strlen($basename) - 1);

    if (strpos($basename, '.') !== false) {
        $extension = end(explode('.', $path));
        $filename = substr($basename, 0, strlen($basename) - strlen($extension) - 1);
    } else {
        $extension = '';
        $filename = $basename;
    }

    return array
        (
        'dirname' => $dirname,
        'basename' => $basename,
        'extension' => $extension,
        'filename' => $filename
    );
}

?>