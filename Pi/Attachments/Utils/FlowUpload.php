<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FlowUpload
 *
 * @author raju
 */

namespace CodePi\Attachments\Utils;

use Flow\Config;
use Flow\Request;
use Flow\Basic;
use URL;

class FlowUpload implements FileUpload {

    //put your code here
    private $size = 0;
    private $allowedTypes = array();
    private $container;
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
            $config = new Config();
            $config->setTempDir($this->getContainer());
            $request = new Request();
            $extension = pathinfo($request->getFileName(), PATHINFO_EXTENSION);
            $filename = md5($files['name'] . time()) . '.' . $extension;
            $path = $this->getContainer() . $filename;
            if (Basic::save($path, $config, $request)) {
                return array('error' => 'success', 'original_name' => $request->getFileName(), 'source_path' => $path,
                    'filename' => $filename
                );
            } else {
                if ($request->getTotalChunks() > 1) {
                    return array('success' => true, 'error' => "File is still uploading. Please wait..");
                } else {
                    return array('error' => "Failed to upload.");
                }
            }
        } else {
            return $validate;
        }
    }

    function tempfile($files) {
        $this->files = $files;
        $files = $this->getFiles();

        $validate = $this->validate(false);
        if ($validate['error'] != 'success') {
            return $validate;
        } else {
            return array('error' => 'success', 'original_filename' => $files['name'], 'filename' => $path);
        }
    }

    function fileSize() {
        $files = $this->getFiles();
        return $files['size'];
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
            if ($files['size'] > $this->getSize()) {
                return array('error' => 'File is too large');
            }
        }

        if (!empty($allowed_types)) {
            $pathinfo = pathinfo($files['name']);

            $ext = strtolower($pathinfo['extension']);
            if (!in_array(strtolower($ext), $allowed_types)) {
                $these = implode(', ', $allowed_types);
                return array('error' => 'File has an invalid extension, it should be one of ' . $these . '.');
            }
        }
        return array('error' => 'success');
    }

}
