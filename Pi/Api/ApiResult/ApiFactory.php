<?php

namespace CodePi\Api\ApiResult;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 
 */
class ApiFactory {

    static function getApiName($type, $key_value) {
        
        $class = ['mis' => 'MIS', 'uber' => 'UBER', 'qaarth' => 'Qaarth'];
        
        if (isset($class[$type])) {
            $str = 'CodePi\Api\ApiResult\\' . $class[$type];
            
            if (class_exists($str)) {
                return new $str($key_value);
            } else {
                throw new \Exception("Invalid Api Class.");
            }
        } else {
            throw new \Exception("Invalid Api " . $type);
        }
    }

}
