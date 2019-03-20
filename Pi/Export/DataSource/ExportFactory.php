<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace CodePi\Export\DataSource;

use Exception;
/**
 * Description of ExportFactory
 *
 * @author enterpi
 */
class ExportFactory {

    static function Factory($type) {

        $class = [1 => 'Excel', 2 => 'Csv'];

        if (isset($class[$type])) {
            $str = 'CodePi\Export\DataSource\\Export'. $class[$type];            
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
