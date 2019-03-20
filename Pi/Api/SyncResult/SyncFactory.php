<?php

namespace CodePi\Api\SyncResult;

class SyncFactory {

    static function getSyncApiName($type, $syncData, $key_value) {
        $class = ['mis' => 'MIS', 'uber' => 'UBER', 'qaarth' => 'Qaarth'];
        
        if (isset($class[$type])) {
            $str = 'CodePi\Api\SyncResult\\' . $class[$type];
            
            if (class_exists($str)) {
                return new $str($syncData, $key_value);
            } else {
                throw new \Exception("Invalid Sync Class.");
            }
        } else {
            throw new \Exception("Invalid Sync " . $type);
        }
    }

}
