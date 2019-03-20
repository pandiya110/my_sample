<?php
namespace CodePi\Base\Libraries\Transfer;

class TransferType {

    static function select($filePath,$uploadType="normal") {
       $class = "CodePi\\Base\\Libraries\\Transfer\\" . ucfirst($uploadType) . "Upload";
        if (!class_exists($class)) {
            throw new \Exception("no '$class' class located in TransferUpload");
        }
        return new $class($filePath, $uploadType);
    }

}
