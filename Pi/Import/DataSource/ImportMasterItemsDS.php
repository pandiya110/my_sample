<?php

namespace CodePi\Import\DataSource;

#use CodePi\Base\Import\ImportFiles;
#use CodePi\Base\Import\ValidateFile;
use CodePi\ImportExportLog\Commands\ImportExportLog;
use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Base\Eloquent\MasterItems;
use CodePi\Base\Libraries\FileReader\ReaderFactory;
use CodePi\Base\Libraries\FileReader;

class ImportMasterItemsDS {
    
    /**
     * Get Data from read excel
     * 
     * @return array
     */
    function getData() {
        DefaultIniSettings::apply();
        $file = storage_path('app') . '/public/Uploads/sample_master.xlsx';
        return $this->readExcelData($file, true);
        
    }
    
    /**
     * Format the After reading the excel data
     * 
     * @param array $readData
     * @return array
     */
    function formatReadData($readData) {
        
        /**
         * Get list of columns from items headers master
         */
        $objItemdDs = new ItemsDataSource();
        $itemsHeaders = $objItemdDs->getItemDefaultHeaders($type = 0);
        foreach ($itemsHeaders as $column) {
            $dbHeaders[trim(strtolower($column['name']))] = trim(strtolower($column['column']));
        }

        $arrayValues = [];
        $fileHeaders = array_keys($readData[0]);
        $matchHeaders = [];
        foreach ($fileHeaders as $key => $value) {
            if (isset($dbHeaders[trim(strtolower($value))]))
                $matchHeaders[trim(strtolower($value))] = $dbHeaders[trim(strtolower($value))];
        }

        $arrayValues = [];
        $k = 0;
        
        foreach ($readData as $key => $value) {
            
            if (!isset($arrayValues[$value['itemid']]) && is_numeric($value['itemid'])) {
                foreach ($matchHeaders as $i => $column) {
                    $arrayValues[$value['itemid']][$column] = isset($value[$i]) ? $value[$i] : '';
                }
            }
        }
        
        return $arrayValues;
    }

    /**
     * Import data into Master table, New recrod will insert , if data exists ,it will update
     * 
     * @param array $fileData
     * @return array
     */
    function importMasterItems(){
        $response = $fileUpc = $dbUpc = $insert = [];
        $insert_count = 0;
        $update_count = 0;
        $objMaster = new MasterItems();
        $objMaster->dbTransaction();
        $readData = $this->getData();
        $fileData = $this->formatReadData($readData);
        
        if (is_array($fileData) && !empty($fileData)) {
            try {
                /**
                 * Get DB Master values
                 */
                $dbData = $this->getMastersItems();
                foreach ($dbData as $value) {
                    $dbUpc[$value['itemsid']] = $value['itemsid'];
                }
                /**
                 * File master values
                 */
                foreach ($fileData as $fileValue) {
                    
                    $upc_number = (string)$fileValue['itemsid'];
                    $fileUpc[$upc_number] = $upc_number;
                }
                /**
                 * get unique value from both array
                 */
                $dbUpcArray = array_unique($dbUpc);
                $fileUpcArray = array_unique($fileUpc);
                unset($fileUpc, $dbUpc);
                /**
                 * find the different from file value to db value
                 */
                $newData = array_diff($fileUpcArray, $dbUpcArray);
                $updateData = array_intersect_key($fileUpcArray, $dbUpcArray);
                unset($fileUpcArray, $dbUpcArray);
                
                $insert_count = count($newData);
                $update_count = count($updateData);
                /**
                 * Insert new data
                 */
                if (!empty($newData)) {
                    foreach ($fileData as $value) {
                        if (in_array($value['itemsid'], $newData)) {
                            $value['itemsid'] = (string)$value['itemsid'];
                            $insert[] = $value;
                        }
                    }
                    
                    if (!empty($insert)) {                        
                        $objMaster->insertMultiple($insert);
                    }
                    unset($insert);
                }
                 /**
                  * BulkUpdate 
                  */   
                 if (!empty($updateData)) {
                    foreach ($updateData as $itemsid) {
                        $update[$itemsid] = $fileData[$itemsid];
                    }
                    $sql = "";
                    foreach ($update as $key => $value) {
                        $sql.="update master_items set ";
                        foreach ($value as $column => $updatevalue) {
                            if (empty($set_column)) {
                                $set_column = $column . " = " . $this->escape($updatevalue) . " ";
                            } else {
                                $set_column .=", " . $column . " = " . $this->escape($updatevalue) . "  ";
                            }                     
                        }
                        $sql.=$set_column . " where itemsid = '" . $key . "';";
                    }
                    if (!empty($sql)) {
                        $objMaster->dbUnprepared($sql);
                    }
                }
                $objMaster->dbCommit();
                $response = ['status' => true, 'data' => ['insert' => $insert_count, 'update' => $update_count]];
            } catch (\Exception $ex) {
                $objMaster->dbRollback();
                $response = ['status' => false, 'data' => $ex->getFile() . ' ' . $ex->getMessage() . ' ' . $ex->getLine()];
            }
        }

        return $response;                
    }
    /**
     * Mysql real escape string
     * @param string $string
     * @return string
     */
    function escape($string) {
        return app('db')->getPdo()->quote($string);
    }
    /**
     * Get Masters items list from mater table
     * @return array
     */
    function getMastersItems(){
        
        $objMasters = new MasterItems();
        $dbResult = $objMasters->get()->toArray();
        
        return $dbResult;
    }
    /**
     * Return only api columns
     * @return array
     */
    function getColumns() {
        $objItemsDs = new ItemsDataSource();
        $itemHeaders = $objItemsDs->getItemDefaultHeaders($type = 0);
        $apiColumns = [];
        foreach ($itemHeaders as $key => $value) {
             $apiColumns[] = $value['column'];            
        }
        return $apiColumns;
    }
    
    /**
     * Read the Excel data, assign excel headers as a array key
     * @param string $file
     * @param boolean $header
     * @return array
     */
    function readExcelData($file, $header = true) {

        $namedDataArray = array();
        $inputFileName = $file;
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($inputFileName);
        $objWorksheet = $objPHPExcel->getActiveSheet();

        if ($header) {
            $highestRow = $objWorksheet->getHighestRow();
            $highestColumn = $objWorksheet->getHighestColumn();
            $headingsArray = $objWorksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true, true);
            $headingsArray = $headingsArray[1];
            $r = -1;
            for ($row = 2; $row <= $highestRow; ++$row) {
                $dataRow = $objWorksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, true, true);                
                if ((isset($dataRow[$row]['A'])) && ($dataRow[$row]['A'] > '')) {
                    ++$r;
                    foreach ($headingsArray as $columnKey => $columnHeading) {                        
                        $namedDataArray[$r][trim(strtolower($columnHeading))] = $dataRow[$row][$columnKey];
                    }
                }
            }
        } else {
            $namedDataArray = $objWorksheet->toArray(null, true, true, true);
        }

        return $namedDataArray;
    }

}
