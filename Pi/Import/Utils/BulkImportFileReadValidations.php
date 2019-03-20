<?php

/**
 * 
 */

namespace CodePi\Import\Utils;

use CodePi\Base\Libraries\FileReader\ReaderFactory;
use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\Base\Import\ValidateFile;
use CodePi\Import\DataSource\BulkImportItemsDs;

/**
 * Class : FileReadValidations
 * 
 */
class BulkImportFileReadValidations {

    /**
     *
     * @var string
     */
    public $files;

    /**
     *
     * @var string
     */
    public $errorMsg = 'success';
    public $isError = false;
    public $numericColumns = array('searched_item_nbr', 'cost', 'upc_nbr', 'fineline_number', 'plu_nbr', 
                                   'was_price', 'supplier_nbr','acctg_dept_nbr', 'base_unit_retail' ,
                                   'forecast_sales');
            
    function setFiles($files) {
        $this->files = $files;
    }

    function getFiles() {
        return $this->files;
    }

    /**
     * Do validations for import items data
     * 
     * @return array;
     */
    function validate() {

        $files = $this->files;
        
        $returnArr = $linkedItemsTab = $arrDiff = $arrDiffLink = $array = $triatNbrExists = [];

        $objBulkImportItemsDs = new BulkImportItemsDs();
        $resultItemsTab = $objBulkImportItemsDs->getData($files, '|', 0);                
        $sheetNames = $objBulkImportItemsDs->getSheetName($files);
        $sheetCount = ($sheetNames) ? count($sheetNames) : 0;
        
        $fileDataWithoutHeaders = array_slice(isset($resultItemsTab[0]) ? $resultItemsTab[0] : [], 1);        
        
        if ($sheetCount > 1) {
            $linkedItemsTab = $objBulkImportItemsDs->getData($files, '|', 1);
        }
        
        if (!empty($resultItemsTab)) {
            if ($sheetCount >= 2) {
                $excelHeaders = isset($resultItemsTab[0]) && isset($resultItemsTab[0][1]) ? $resultItemsTab[0][1] : [];
                $actualHeaders = $objBulkImportItemsDs->importHeaders();
                $arrDiff = array_diff_assoc($actualHeaders, $excelHeaders);

                if (!empty($linkedItemsTab)) {
                    $excelLinkedItems = isset($linkedItemsTab[1]) && isset($linkedItemsTab[1][1]) ? $linkedItemsTab[1][1] : [];
                    $actualLinkedItemsHeaders = $objBulkImportItemsDs->importLinkedItemsHeaders();

//                    foreach ($excelLinkedItems as $columns) {
//                        if (in_array($columns, $actualLinkedItemsHeaders)) {
//                            $array[] = $columns;
//                        }
//                    }
                    //$arrDiffLink = array_diff($actualLinkedItemsHeaders, $array);
                    $arrDiffLink = array_diff_assoc($actualLinkedItemsHeaders, $excelLinkedItems);
                }
            }
        }
        
        if (empty($resultItemsTab) || empty($fileDataWithoutHeaders)) {
            $this->isError = true;
            $this->errorMsg = 'Empty file. Please check the file & upload again.';
        }
        unset($fileDataWithoutHeaders);
        
        if(isset($resultItemsTab[0]) && isset($resultItemsTab[0][1]) && !empty($resultItemsTab) ){            
            $arrNumeric = $this->validateNumericColumns($resultItemsTab[0]);                       
            if(!empty($arrNumeric)){
                $this->isError = true;
                $this->errorMsg = 'Some of the item values are not numeric values, please verify the data';
            }
        }
        
        if ($sheetCount > 2 || $sheetCount < 2) {
            $this->isError = true;
            $this->errorMsg = 'The count of tabs do not match the Marketing SmartForm Template. Please verify that this file uses the correct format.';
        }else if($sheetCount == 2){
            $arrSheetName = $objBulkImportItemsDs->defaultSheetNames();
            $arrDiffSheetName = array_diff($arrSheetName, $sheetNames);
            if(!empty($arrDiffSheetName)){
                $this->isError = true;
                $this->errorMsg = 'The names of the tabs do not match the Marketing SmartForm Template. Please verify that this file uses the correct format.';                
            }
            
            unset($arrDiffSheetName);
        }
        if(isset($resultItemsTab[0]) && isset($resultItemsTab[0][1]) && !empty($resultItemsTab) ){
             $triatNbrExists = $this->validateTraitNbr($resultItemsTab[0]);             
        }
        
        if (!empty($arrDiff)) {
            $this->isError = true;
            $this->errorMsg = 'The columns from the results tab in the file do not match the Marketing SmartForm Template. Please verify that this file uses the correct format.';
        }else if (!empty($arrDiffLink)) {
            $this->isError = true;
            $this->errorMsg = 'The columns from the linked items tab in the file do not match the Marketing SmartForm Template. Please verify that this file uses the correct format.';
        }else if(!empty($triatNbrExists)){
            $this->isError = true;
            $this->errorMsg = 'Invalid price versions found for items in the file. Please verify the data.';
        }
                
        if ($this->isError == true) {
            unlink($files);
        }
        unset($resultItemsTab, $linkedItemsTab);
        $returnArr['error'] = $this->errorMsg;
        
        return $returnArr;
    }

    /**
     * Find the reader method based on file extentions
     * 
     * Read and Get data from uploaded files
     * 
     * @param string file path $file
     * @param string $delimeter
     * @return array
     */
    function getData($file, $delimeter) {
        DefaultIniSettings::apply();
        $objReader = ReaderFactory::select($file, $delimeter);
        return $objReader->getData($file, true);
    }
    /**
     * 
     * @param type $data
     * @return type
     */
    function validateNumericColumns($data) {
        $arrColumns = [];
        if (!empty($data)) {
            $objBulkDs = new BulkImportItemsDs();
            $arrData = $objBulkDs->formatItemsArray($data, $type = 0);
            foreach ($arrData as $value) {
                foreach ($this->numericColumns as $columns) {

                    if (isset($value[$columns]) && !empty(trim($value[$columns]))) {
                        $formatValue = preg_replace('/[\$,~]/', '', $value[$columns]);                       
                        if (!is_numeric(trim($formatValue))) {
                            $arrColumns[$columns] = $formatValue;
                        }
                    }
                }
            }
        }
        
        return $arrColumns;
    }
    
    function validateTraitNbr($data) {
        $isExists = false;
        $objBulkDs = new BulkImportItemsDs();
        $arrData = $objBulkDs->formatItemsArray($data, $type = 0);
        foreach ($arrData as $key => $row) {
            if (!empty($row['versions'])) {
                $traitNbr = $objBulkDs->prepareVersionsbyTraitNbr($row['versions']);
                $dbTraits = $objBulkDs->getDbTraitNbr($traitNbr);
                $arrDiff = array_diff($traitNbr, $dbTraits);
                if (!empty($arrDiff)) {
                    $isExists = true;
                    break;
                }
            }
        }
        return $isExists;
    }

}

?>