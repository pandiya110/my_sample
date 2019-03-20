<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FileUpload
 *
 * @author raju
 */
namespace CodePi\Attachments\Utils;

interface FileUpload {
    //put your code here
    
    function getSize();
    function getContainer();
    function getAllowedTypes();
    function getFiles();

    function setSize($size);
    function setContainer($container);
    function setAllowedTypes($allowedtypes);
    function setFiles($files);

    //function save($file);

    function save();
    function tempfile($file);
    function fileSize();
    function validate($is_save);
}
