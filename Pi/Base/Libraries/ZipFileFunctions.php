<?php
namespace CodePi\Base\Libraries;

class ZipFileFunctions {
    
    function createZipFolder($zipFolderName,$container) {
//        $tempFilePath = public_path()."/uploads/temp/";
        //echo "<pre>";print_r($container);exit;
        $zipFolderPath = $container."/". $zipFolderName;
        //echo "<pre>";print_r($zipFolderPath);exit;
        if (file_exists($zipFolderPath)) {
            self::deleteFolder($zipFolderPath);
        }
        //self::createFolder($zipFolderPath);
        return $zipFolderPath;
    }
    function createFolder ($dirPath) {
        @mkdir($dirPath, 0777, true);
        @chmod($dirPath, 0777);
    }
    static function deleteFolder($dirname) {
        $dir_exist = FALSE;
           if (is_dir($dirname))
               $dir_exist = opendir($dirname);
         
           if($dir_exist){
               while ($file = readdir($dir_exist)) {
                   if ($file != "." && $file != "..") {
                       if (!is_dir($dirname . "/" . $file))
                           @unlink($dirname . "/" . $file);
                       else
                           self::deleteFolder($dirname . '/' . $file);
                   }
               }
               @closedir($dir_exist);
           }elseif (file_exists($dirname)) {
               @unlink($dirname);
           }else{
               return FALSE;
           }
           @rmdir($dirname); 
           return true;
    }
    /****
     * To compress
     */
    function compressFolder($zipFileName,$source,$destination) { 
        ini_set('memory_limit', '-1');
        $pub = preg_replace("/[^A-Za-z0-9\-_.]+/", "", html_entity_decode($zipFileName, ENT_QUOTES));
        $zipFolderName = substr($pub, 0, 100); // . "_" . date("dmYHis");
        $zipFolderPath = $this->createZipFolder($zipFolderName,$source);
        $zipFolderName = $zipFolderName . ".zip";
        $zipFileName = $destination . '/' . $zipFolderName;
        //$zipFileName = public_path() . '/uploads/temp/' . $zipFolderName;
        if (file_exists($zipFileName)) {
            unlink($zipFileName);
        }
        $exclusiveLength = strlen("$source/");
        $zipObj = new \ZipArchive();
        $zipObj->open($zipFileName, \ZIPARCHIVE::CREATE);
        self::folderToZip($source, $zipObj, $exclusiveLength);
        $zipObj->close();
        return $zipFileName;
        //return true;
    }
    static function folderToZip($folder, &$zipFile, $exclusiveLength) { 
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip. 
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {                    // Add sub-directory. 
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }
}


