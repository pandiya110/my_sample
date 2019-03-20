<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CustomClass
 *
 * @author raju
 */

namespace CodePi\Base\Libraries;

class CustomFunctions {

    //put your code here

    /* $options=array(id,name) */
    static function SelectBox($options, $SelectedId = '', $isSelect = true, $default = array('' => 'Select')) {

        $selectbox = '';
        if (isset($options)) {
            if (isset($default) && $isSelect) {
                foreach ($default as $l => $m) {
                    $selectbox.="<option value='" . $l . "' >" . $m . "</option>";
                }
            }
            foreach ($options as $p => $q) {
                $q = (array) $q;
                $SelectedText = '';

                if (isset($q['id']) && $q['name']) {
                    if ($SelectedId == $q['id'])
                        $SelectedText = 'selected';

                    $selectbox.="<option value='" . $q['id'] . "' " . $SelectedText . ">" . $q['name'] . "</option>";
                }
            }
        }
        return $selectbox;
    }

    static function __SelectBox($object) {
        $selectBox = array();
        if (!empty($object) && isset($object)) {
            foreach ($object as $obj) {
                $selectBox[''] = 'Select';
                $selectBox[$obj->id] = $obj->name;
            }
        }

        return self::SelectBox($selectBox);
    }

    //


    static function console($element, $type = 'echo') {
        if ($type == 'echo')
            echo $element;
        else if ($type == 'print') {
            echo "<pre>";
            print_r($element);
        } else if ($type == 'var_dump') {
            echo "<pre>";
            var_dump($element);
        } else if ($type == 'console') {
            echo "<script>console.log('" . $element . "')</script>";
        }
    }

    function number_format($string) {

        if (!empty($string)) {

            $format = number_format($string, 2);
            $result = '$' . $format;
        } else {

            $result = '-';
        }

        return $result;
    }

    function jsonDecode($result_data) {

        $fields = '';
        if (!empty($result_data)) {
            $data = json_decode($result_data, true);
            $fields = implode(', ', array_keys($data));
        }

        return $fields;
    }

    function time_elapsed_string($datetime, $full = false) {
        $now = new \DateTime;
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'y',
            'm' => 'm',
            'w' => 'w',
            'd' => 'd',
            'h' => 'hr',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . '' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full)
            $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
    
    function time_elapsed_string1($time) {
        $time = time() - strtotime($time); // to get the time since that moment
        $time = ($time < 1) ? 1 : $time;
        $tokens = array(
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        foreach ($tokens as $unit => $text) {
            if ($time < $unit)
                continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '');
        }
    }

    function nice_number($n) {
        // first strip any formatting;
        $n = (0 + str_replace(",", "", $n));

        // is this a number?
        if (!is_numeric($n))
            return false;

        // now filter it;
        if ($n > 1000000000000)
            return round(($n / 1000000000000), 2) . ' trillion';
        elseif ($n > 1000000000)
            return round(($n / 1000000000), 2) . ' billion';
        elseif ($n > 1000000)
            return round(($n / 1000000), 2) . ' million';
        elseif ($n > 1000)
            return round(($n / 1000), 2) . ' thousand';

        return number_format($n);
    }

}
