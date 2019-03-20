<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UploadType
 *
 * @author enterpi
 */

namespace CodePi\Attachments\Utils;

use Exception;

class UploadType {

    //put your code here

    static function Factory($upload_type) {
        $upload = $upload_type . "Upload";
        $str = 'CodePi\Attachments\Utils\\' . $upload;
        if (class_exists($str)) {
            return new $str;
        } else {
            throw new Exception("Invalid Upload type given.");
        }
    }

}
