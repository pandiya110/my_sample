<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ReportViewFactory
 *
 * @author enterpi
 */

namespace CodePi\ReportView\Utils;

use Exception;

class ReportViewFactory {

    static function Factory($type) {
        $source = $type . 'ReportViewDs';
        $class = 'CodePi\ReportView\DataSource\\' . $source;
        if (class_exists($class)) {
            return new $class;
        } else {
            throw new Exception("Invalid Source type given.");
        }
    }

}
