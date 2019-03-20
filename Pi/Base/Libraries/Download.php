<?php
namespace CodePi\Base\Libraries; 
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Download
 *
 * @author raju
 */
class Download {
	// put your code here
	/*
	 * $filename full path of Filename
	 */
	static function start($filename, $rename = NULL) {
		ini_set ( 'memory_limit', '-1' );
		$file = basename ( $filename );
		
		if (empty ( $rename ))
			$rename = $file;
		
		$ext = pathinfo ( $filename, PATHINFO_EXTENSION );
		
		if (isset ( $ext ) && empty ( $ext )) {
			$ext = pathinfo ( $rename, PATHINFO_EXTENSION );
		}
		
		header ( "Cache-Control: public" );
		header ( "Content-Description: File Transfer" );
		header ( 'Content-disposition: attachment; filename=' . basename ( $rename ) );
		if ($ext [count ( $ext ) - 1] == "zip")
			header ( "Content-Type: application/zip" );
		else if ($ext [count ( $ext ) - 1] == "xls" || $ext [count ( $ext ) - 1] == "xlsx" || $ext [count ( $ext ) - 1] == "csv")
			header ( "Content-Type: application/vnd.ms-excel" );
		else if ($ext [count ( $ext ) - 1] == "doc" || $ext [count ( $ext ) - 1] == "docx")
			header ( "Content-Type: application/msword" );
		else
			header ( "Content-type: application/octet-stream" );
		
		header ( "Content-Transfer-Encoding: binary" );
		header ( 'Content-Length: ' . filesize ( $filename ) );
		readfile ( $filename );
	}
}
