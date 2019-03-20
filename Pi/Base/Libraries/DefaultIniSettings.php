<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace CodePi\Base\Libraries; 

class DefaultIniSettings {
    
    static function apply(){
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 2000);
    }
}

