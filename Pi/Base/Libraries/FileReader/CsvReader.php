<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Admin
 *
 * @author enterpi
 */

namespace CodePi\Base\Libraries\FileReader;

use CodePi\Base\Libraries\FileReader\iReader as iReader;
use Config;

class CsvReader implements iReader {

    public $file = NULL;
    public $headers = NULL;
	public $delimitor = ",";
    public function __construct($file, $seperator=",") {
        $this->file = $file;
        $this->delimitor = $seperator;
    }

    function getData() {
        $arrCSV = array();
        $arrFileData = array();
        $fileHeaders = array();
        $row = 0;
        //$delimeter = $this->delimitor;
        $path = public_path($this->file);
        //$intFileRowsLimit = Config::get('constants.fileRows');
        //Check File Permissions :: Start
        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 10000000, $this->delimitor)) !== FALSE) {
                $num = count($data);
                for ($column = 0; $column < $num; $column++) {
                    if (0 == $row) {
                        $fileHeaders[] = $data[$column];
                    }else{
						$arrFileData[$row][$column] = $data[$column];
					}
                    
                }
                $row++;
            }
            fclose($handle);
        }//Check File Permissions :: End
        $arrCSV['headers'] = $fileHeaders;
        $arrCSV['data'] = array_values($arrFileData);
        return $arrCSV;
    }

    function setHeaders($headers) {
        $this->headers = $headers;
    }

    function getHeaders() {
        return $this->headers;
    }

    function validation() {
        $arrMismatch = array();
        $arrError = array();
        $arrCSV = $this->getData();
        if (empty($arrCSV['data'])) {
            $arrError = array('type' => 'error', 'message' => "No Data", 'result' => $arrCSV['data'], 'success' => FALSE);
        } else {
            //Input
            $arrFileHeaders = $arrCSV['headers'];
            $arrFileHeaders = $this->removeAllSpacesInArray($arrFileHeaders);
            //Admin File Headers
            $arrFixedHeaders = $this->removeAllSpacesInArray(array_values($this->headers));
            //Compute Both Input Headers And Admin File Headers
            if (count($arrFixedHeaders) == count($arrFileHeaders)) {
                $arrMismatch = array_diff($arrFixedHeaders, $arrFileHeaders);
            }

            if ((count($arrFixedHeaders) != count($arrFileHeaders)) || !empty($arrMismatch)) {
                $arrError = array('type' => 'error', 'message' => "Headers Mismatched", 'result' => $arrCSV['headers'], 'success' => FALSE);
            }
        }
        return $arrError;
    }

    function removeAllSpacesInArray($data) {
        $returnArr = array();
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $returnArr[$i] = preg_replace('/[^A-Za-z0-9_\-]/', '', strtolower(preg_replace("/ +/", "", $data[$i])));
            }
        }
        return $returnArr;
    }

}
