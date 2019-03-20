<?php

/**
 * Description of Admin
 *
 * @author enterpi
 */

namespace CodePi\Base\Libraries\FileReader;

class ReaderFactory {

    static function select($file, $delimitor=",") {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ((in_array($ext, ['xls','xlsx']))) {
            $ext = "Excel";            
        }else  {
            $ext = "csv";
        }
        $class = "CodePi\\Base\\Libraries\\FileReader\\" . ucfirst($ext) . "Reader";
        if (!class_exists($class)) {
            throw new FileReaderNotFoundException("no '$class' class located in FileReader");
        }
        return new $class($file, $delimitor);
    }

}
