<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DateFormat
 *
 * @author enterpi
 */
class DatesFormat {
	// put your code here
	static public function emptydateformat($date) {
		if ($date == "0000-00-00 00:00:00" || $date == NULL)
			$datevalue = "--";
		else
			$datevalue = date ( 'M d, Y h:i A', strtotime ( $date ) );
		
		return $datevalue;
	}
	static function Format($date, $dateFormat = 'Y-m-d H:i:s') {
		if ($date == "0000-00-00 00:00:00" || $date == NULL || empty ( $date )) {
			return '';
		} else {
			return date ( $dateFormat, strtotime ( $date ) );
		}
	}
	static public function validateDate($date, $format = 'Y-m-d H:i:s') {
		$d = DateTime::createFromFormat ( $format, $date );
		echo $d && $d->format ( $format ) == $date;
	}
}
