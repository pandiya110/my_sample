<?php
namespace CodePi\Base\Libraries\FileReader;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MyExcel
 *
 * @author raju
 */
// require __DIR__ . '/../../vendor/autoload.php';
class ExcelReader {
        private $fileSettings;
	function __construct() {
	}
        function setSettings ($settings){
            $this->fileSettings = $settings;
        }
        function getSettings (){
            return $this->fileSettings;
        }
	function sampleraju() {
		
		// $x=func_get_args(); print_r($x);
		// Read your Excel workbook
		set_time_limit ( 0 );
                 $filename = storage_path('app/public').'/test/072817.xlsx';
		//$filename = public_path () . '/uploads/4.xlsx';
		$data = array ();
		$inputFileType = PHPExcel_IOFactory::identify ( $filename );
		$objReader = PHPExcel_IOFactory::createReader ( $inputFileType );
		$objReader->setReadDataOnly ( false );
		$reader = $objReader->load ( $filename );
		
		$count = 1; // $reader->getSheetCount();
		$sheetNames = $reader->getSheetNames ();
		for($i = 0; $i < $count; $i ++) {
			$objWorksheet = $reader->setActiveSheetIndex ( $i ); // first sheet
			$ObjectCell = array ();
			$ObjectMerged = array ();
			foreach ( $objWorksheet->getMergeCells () as $cells ) {
				$ObjectCell [] = $cells;
				
				$rows = explode ( ':', $cells );
				
				$start = preg_replace ( '/[0-9]+/', '', $rows [0] );
				$column = preg_replace ( '/[A-Z]+/', '', $rows [0] );
				
				$start2 = preg_replace ( '/[0-9]+/', '', $rows [1] );
				$column2 = preg_replace ( '/[A-Z]+/', '', $rows [1] );
				
				$ObjectMerged [] = array (
						'from' => array (
								'row' => $start,
								'row_value' => PHPExcel_Cell::columnIndexFromString ( $start ),
								'column' => $column 
						),
						'to' => array (
								'row' => $start2,
								'row_value' => PHPExcel_Cell::columnIndexFromString ( $start2 ),
								'column' => $column2 
						) 
				);
			}
			// echo PHPExcel_Cell::columnIndexFromString('AA');
			echo "<pre>"; // die();
			print_r ( $ObjectCell );
			print_r ( $ObjectMerged );
			$highestRow = $objWorksheet->getHighestRow (); // here 5
			$highestColumn = $objWorksheet->getHighestColumn (); // here 'E'
			$highestColumnIndex = PHPExcel_Cell::columnIndexFromString ( $highestColumn ); // here 5
			for($row = 1; $row <= $highestRow; ++ $row) {
				for($col = 0; $col < $highestColumnIndex; ++ $col) {
					
					/*
					 * if ($col == 5 && $row == 2) {
					 * //$value = 456789012435;
					 * $value=$objWorksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
					 * } else {
					 * $value = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
					 * }
					 */
					// $value = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
					$value = $objWorksheet->getCellByColumnAndRow ( $col, $row )->getCalculatedValue ();
					// $cell->getColumn();
					if ($row == 9) {
						// echo PHPExcel_Cell::columnIndexFromString($objWorksheet->getColumn()); echo "<br>";
						// echo $value=$objWorksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
						// 000000000000000000000 $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
						// $mergedCells=
						// echo $ColIndex= PHPExcel_Cell::stringFromColumnIndex($col);
						// echo "<br>";
						/*
						 * foreach ($ObjectCell as $cells) {
						 *
						 * if ($objWorksheet->getCell($ColIndex.$row)->isInRange($cells)) {
						 * echo 'Cell is merged!';
						 * // /break;
						 * }
						 * }
						 */
					}
					
					if (is_array ( $data )) {
						if (! empty ( $value ))
							$data [$i] [$row] [$col + 1] = $value;
					}
				}
			}
		}
		echo "<pre>";
		// print_r($data[4]);
		print_r ( $data );
	}
	function getData($filename,$show_empty_fields) {
           // echo 333;die;
           // echo $filename;die;
		if (file_exists ( $filename )) {                  
			set_time_limit ( 0 );
			$data = array ();
                       // print_r($filename);die;
			$inputFileType = \PHPExcel_IOFactory::identify ( $filename );
			$objReader = \PHPExcel_IOFactory::createReader ( $inputFileType );
			$objReader->setReadDataOnly ( false );
			$reader = $objReader->load ( $filename );
			$fileHeaders = [];
                        $excelData = [];
                        $count = $reader->getSheetCount();
                        $highestColumnIndex = 0;
			$sheetNames = $reader->getSheetNames ();
			for($i = 0; $i < $count; $i ++) {
				$objWorksheet = $reader->setActiveSheetIndex ( $i ); // first sheet
				$highestRow = $objWorksheet->getHighestRow ();
				 // here 5
				$highestColumn = $objWorksheet->getHighestColumn (); // here 'E'                                
				$highestColumnIndex = ($highestColumnIndex > 0)?$highestColumnIndex:\PHPExcel_Cell::columnIndexFromString ( $highestColumn ); // here 5				
				for($row = 0; $row <= $highestRow; ++ $row) {
					for($col = 0; $col < $highestColumnIndex; ++ $col) {						
						// / $value= $objWorksheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
						$value = $objWorksheet->getCellByColumnAndRow ( $col, $row )->getValue ();                                                                                                
                                                if (is_array ( $data ) && !empty($value)) {
                                                    if(isset($show_empty_fields)){
                                                        $data [$i] [$row] [$col] = $value;
                                                    }else{
                                                            $data [$i] [$row] [] = $value;
                                                    }
                                                }                           /*if($row == 0 ){
                                                        if(!empty($value))
                                                            $fileHeaders[] =  $value;                                                            
                                                    }else{
                                                        if (is_array ( $data ) && $col < count($fileHeaders)) {
                                                                $data [$i] [$row] [] = $value;
                                                        }
                                                    }*/
					}           
				}
				$highestColumnIndex = 0;
			}
			//$excelData['headers'] = $fileHeaders;                        
                        //$excelData['data'] = array_values($data);
			return $data;
		} else {
			
			throw new \Exception ( 'File not Found' );
                    //return 'File not Found';
		}
	}
	
	// function cleanData(&$str)
	// {
	// $str = preg_replace("/\t/", "\\t", $str);
	// $str = preg_replace("/\r?\n/", "\\n", $str);
	// if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
	// }
	//
	// // filename for download
	// $filename = "website_data_" . date('Ymd') . ".xls";
	//
	// header("Content-Disposition: attachment; filename=\"$filename\"");
	// header("Content-Type: application/vnd.ms-excel");
	//
	// $flag = false;
	// foreach($data as $row) {
	// if(!$flag) {
	// // display field/column names as first row
	// echo implode("\t", array_keys($row)) . "\r\n";
	// $flag = true;
	// }
	// array_walk($row, 'cleanData');
	// echo implode("\t", array_values($row)) . "\r\n";
	// }
}
