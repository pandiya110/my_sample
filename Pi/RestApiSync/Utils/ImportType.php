<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace CodePi\RestApiSync\Utils;

use Exception;

/**
 * Description of ImportType
 *
 * @author enterpi
 */
class ImportType {

    static function Factory($type) {

        $class = [1 => 'Events', 2 => 'Items'];
        
        if (isset($class[$type])) {
            $str = 'CodePi\RestApiSync\DataSource\\' . $class[$type].'DataSource';            
            if (class_exists($str)) {
                return new $str;
            } else {
                throw new Exception("Invalid Source type given.");
            }
        } else {
            throw new Exception("Invalid Source " . $type);
        }
    }

}
