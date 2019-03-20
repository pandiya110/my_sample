<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of sizeConversion
 *
 * @author enterpi
 */
class SizeConversion {
	// put your code here
	static public function sizeconversionmethod($filesize) {
		if ($filesize < 1048676)
			RETURN number_format ( $filesize / 1024, 1 ) . " KB";
		if ($filesize >= 1048676 && $filesize < 1073741824)
			RETURN number_format ( $filesize / 1048576, 1 ) . " MB";
		if ($filesize >= 1073741824 && $filesize < 1099511627776)
			RETURN number_format ( $filesize / 1073741824, 2 ) . " GB";
		if ($filesize >= 1099511627776)
			RETURN number_format ( $filesize / 1099511627776, 2 ) . " TB";
		if ($filesize >= 1125899906842624) // Currently, PB won't show due to PHP limitations
			RETURN number_format ( $filesize / 1125899906842624, 3 ) . " PB";
	}
}
