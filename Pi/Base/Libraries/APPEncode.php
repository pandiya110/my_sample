<?php
namespace CodePi\Base\Libraries;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MyEncode
 *
 * @author raju
 */
class APPEncode {
	// put your code here
	static function filterString($str) {
		if (isset ( $str ) && $str != '' && $str != '0' && ! is_array ( $str )) {
			$str = (trim ( htmlentities ( $str, ENT_QUOTES, "UTF-8" ) ));
			$str = str_replace ( "&amp;", "&", $str );
			
			return $str;
		} else
			return $str;
	}
	static function filterStringDecode($str, $spchar = false) {
		if (isset ( $str ) && $str != '' && $str != '0') {
			$str = trim ( htmlspecialchars_decode ( html_entity_decode ( $str, ENT_QUOTES, 'UTF-8' ) ) );
			
			if ($spchar) {
				$str = iconv ( "UTF-8", "ISO-8859-1//TRANSLIT", $str );
				// $str=utf8_decode($str);
			}
			return $str;
		} else
			return $str;
	}
	static function arrayFilterStringDecode($array) {
		if (is_array ( $array )) {
			// foreach()
		}
	}
}
