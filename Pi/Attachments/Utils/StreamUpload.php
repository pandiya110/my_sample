<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of StreamUpload
 *
 * @author enterpi
 */

namespace CodePi\Attachments\Utils;

class StreamUpload implements FileUpload {

    //put your code here
    private $size = 0;
    private $allowedTypes = array();
    private $container = '/tmp';
    private $files;

    public function getSize() {
        return $this->size;
    }

    public function getAllowedTypes() {
        return $this->allowedTypes;
    }

    public function getContainer() {
        return $this->container;
    }

    public function getFiles() {
        return $this->files;
    }

    public function setSize($size) {
        $this->size = $size;
    }

    public function setAllowedTypes($allowedTypes) {
        $this->allowedTypes = $allowedTypes;
    }

    public function setContainer($container) {
        $this->container = $container;
    }

    public function setFiles($files) {
        $this->files = $files;
    }

    function save() {
        //$this->files = $files;

        $files = $this->getFiles();

        $validate = $this->validate(true);
        if (isset($validate['error']) && $validate['error'] == 'success') {

            $pathinfo = pathinfo($files);
            $ext = strtolower($pathinfo['extension']);
            $path = $this->getContainer() . time() . mt_rand() . '.' . $ext;

            $temp_filename = $this->tempfile();
            $temp = $temp_filename['filename'];
            $target = fopen($path, "w");
            fseek($temp, 0, SEEK_SET);
            stream_copy_to_stream($temp, $target);
            fclose($target);

            return array('error' => 'success', 'original_name' => $files, 'filename' => $path);
        } else {
            return $validate;
        }
    }

    function saveFile($files) {
        $this->files = $files;
        $files = $this->getFiles();
        $validate = $this->validate(true);
        if (isset($validate['error']) && $validate['error'] == 'success') {
            $pathinfo = pathinfo($files);
            $ext = strtolower($pathinfo['extension']);

            $path = $this->getContainer() . md5($files . time()) . '.' . $ext;

            $temp_filename = $this->tempfile();
            $temp = $temp_filename['filename'];
            $target = fopen($path, "w");
            fseek($temp, 0, SEEK_SET);
            stream_copy_to_stream($temp, $target);
            fclose($target);

            return array('error' => 'success', 'original_filename' => $files, 'filename' => $path);
        } else {
            return $validate;
        }
    }

    function tempfile($files = NULL) {
        if (!empty($files)) {
            $this->files = $files;
            $validate = $this->validate(false);
            if ($validate['error'] != 'success') {
                return $validate;
            }
        }
        $files = $this->getFiles();
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        return array('error' => 'success', 'original_filename' => $files, 'filename' => $temp);
    }

    function fileSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])) {
            $size = (int) $_SERVER["CONTENT_LENGTH"];
        } else {
            throw new Exception('Getting content length is not supported.');
        }
        return $size;
    }

    function validate($is_save = true) {
        $allowed_types = $this->getAllowedTypes();
        $files = $this->getFiles();
        if ($is_save) {
            if (!is_writable($this->getContainer())) {
                return array('error' => "Server error. Upload directory isn't writable.");
            }
        }
        if ($this->getSize()) {
            $size = $this->fileSize();
            if ($size > $this->getSize()) {
                return array('error' => 'File is too large');
            }
        }
        if (!empty($allowed_types)) {
            $pathinfo = pathinfo($files);
            $ext = strtolower($pathinfo['extension']);
            if (!in_array(strtolower($ext), $this->getAllowedTypes())) {
                $these = implode(', ', $this->getAllowedTypes());
                return array('error' => 'File has an invalid extension, it should be one of ' . $these . '.');
            }
        }
        return array('error' => 'success');
    }

}
