<?php

namespace CodePi\Base\Libraries;

class PiDate {

    static function dateFormat($date, $dateFormat='Y-m-d') {
                if ($date != '') {
                    return date($dateFormat, strtotime($date));
                } else {
                    return '';
                }
            }

    

}
