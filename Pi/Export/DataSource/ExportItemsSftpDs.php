<?php

namespace CodePi\Export\DataSource;

#use PHPExcel;
#use PHPExcel_IOFactory;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
#use PHPExcel_Style_Border,
#    PHPExcel_Style_Alignment,
#    PHPExcel_Style_Fill;
#use PHPExcel_Style_NumberFormat;
#use CodePi\Items\Commands\GetItemsList;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\ImportExportLog\Commands\ImportExportLog;
#use PHPExcel_Helper_HTML;
#use CodePi\Items\Commands\GetLinkedItemsList;
use CodePi\Base\Eloquent\Items;
#use CodePi\Items\Utils\ItemsGridDataResponse;
#use CodePi\Export\DataSource\ExportExcel;
use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\Channels\DataSource\ChannelsDataSource;
use CodePi\Items\Utils\ItemsUtils;
use Illuminate\Support\Facades\Storage;
use CodePi\Export\DataSource\ExportData;
use CodePi\Base\Eloquent\SystemsLogs;
use DateTime,
    DateTimeZone,
    DateInterval;
use ZipArchive;

class ExportItemsSftpDs {
    
    /**
     * 
     * @param type $command
     * @return boolean
     */
    function getItemsDataToExport($params) {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        //$params = $command->dataToArray();        
        $reqType = isset($params['requireType']) ? $params['requireType'] : 1;
        $itemsType = $params['itemsType'];
        $response = [];
        $objExport = new ExportData();
        $objSystemLog = new SystemsLogs();
        try {

            $return = $this->getAllItemsFromDB($reqType, $itemsType, $params['cronTime']);
            $arrItems = $this->formatItemsQueryResult($return, $itemsType);            
            if (!empty($arrItems)) {
                $resultType = ($itemsType == '0') ? 'ResultItems' : 'LinkedItems';
                $fileName = 'ListBuilder_' . $resultType . '_' . date('mdYHis').'_'.$params['cronTime'] . '.csv';
                $dirPath = storage_path('app') . '/public/Export/export_items_to_sftp/' . $fileName;
                $fp = fopen($dirPath, 'w');
                $headers = isset($arrItems[0]) ? array_keys($arrItems[0]) : [];
                if (isset($headers[0])) {
                    unset($headers[0]);
                }

                fputcsv($fp, $headers, '|');
                $currencyFormat = $this->currencyFormatColumn();
                foreach ($arrItems as $fields) {
                    if ($fields['is_excluded'] == true && $itemsType == '0') {
                        $fields['Ad Block'] = 'ZDELETE';
                    }
                    unset($fields['is_excluded']);
                    foreach ($fields as $key => $value) {
                        if (in_array($key, $currencyFormat)) {
                            $fields[$key] = !empty($value) ? '$ ' . floatval($value) : $value;
                        }
                    }
                    fputcsv($fp, $fields, '|');
                }
                sleep(2);
                if (file_exists($dirPath)) {                    
                    chmod($dirPath, 0777);
                    $objSystemLog->saveRecord(['action' => 'ExportToSftp',                                                                                                 
                                               'master_id' => 0, 
                                               'filename' => $fileName, 
                                               'message' => $itemsType. ' - File has been generate successfully']
                                               );
                }
                fclose($fp);
                $response = ['status' => true, 'dirPath' => $dirPath];
                
            }else{
                
                $objSystemLog->saveRecord(['action' => 'ExportToSftp',                                                                                     
                                           'master_id' => 0, 
                                           'filename' => '', 
                                           'message' => $itemsType.' -  No data available to prepare csv file']
                                          );
            }
        } catch (\Exception $ex) {
            $response = ['status' => false, 'message' => $ex->getMessage() . ' ' . $ex->getFile() . ' ' . $ex->getLine()];
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }

        return $response;
    }

     /**
      * 
      * @param type $reqType
      * @param type $itemsType
      * @return type
      */
     function getAllItemsFromDB($reqType = 2, $itemsType = '0', $cronTime) {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $returnArray = [];
        try {
            $objItemsDs = new ItemsDataSource();
            $getColumns = $objItemsDs->getItemDefaultHeaders($linked_item_type = 0);
            $column = [];
            foreach ($getColumns as $key => $value) {
                $column[] = 'trim(' . $value['aliases_name'] . '.' . $key . ') as ' . $key;
            }
            $columnName = implode($column, ',');
            unset($column);
            $startTime=$endTime=null;
            $objItems = new Items();
            if ($reqType == 2) {
                $startTime = date('Y-m-d H:i:s');
                $endTime = date('Y-m-d H:i:s');

                $inputDate = date('Y-m-d') . ' ' . $cronTime;
                if ($cronTime == '06:00') {
                    $date = new DateTime($inputDate, new DateTimeZone("CST"));
                    $date->sub(new DateInterval('PT10H'));
                    $startTime = $date->format('Y-m-d H:00:00');
                    $endTime = date('Y-m-d 06:00:00');
                } else if ($cronTime == '11:00') {
                    $date = new DateTime($inputDate, new DateTimeZone("CST"));
                    $date->sub(new DateInterval('PT5H'));
                    $startTime = $date->format('Y-m-d H:00:00');
                    $endTime = date('Y-m-d 11:00:00');
                } else if ($cronTime == '16:00') {
                    $date = new DateTime($inputDate, new DateTimeZone("CST"));
                    $date->sub(new DateInterval('PT5H'));
                    $startTime = $date->format('Y-m-d H:00:00');
                    $endTime = date('Y-m-d 16:00:00');
                } else if ($cronTime == '20:00') {
                    $date = new DateTime($inputDate, new DateTimeZone("CST"));
                    $date->sub(new DateInterval('PT4H'));
                    $startTime = $date->format('Y-m-d H:00:00');
                    $endTime = date('Y-m-d 20:00:00');
                }
            }
            //echo $startTime.'-'.$endTime; exit; 
            $objResult = $objItems->dbTable('i')
                                  ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                                  ->join('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                                  ->join('events as e', 'e.id', '=', 'i.events_id')
                                  ->select('i.id', 'e.name as event_name', 'is_excluded', 'i.events_id', 'i.items_import_source')
                                  ->selectRaw($columnName)
                                  ->where('e.is_draft', '0')
                                  ->where('items_type', $itemsType)
                                  ->where('i.is_no_record', '0')
                                  ->where(function ($query) use ($reqType, $startTime, $endTime) {
                                    if ($reqType != 1) {
                                        //$query->whereRaw('DATE(i.last_modified) = DATE(NOW())');
                                        $query->whereRaw('i.last_modified BETWEEN "' . $startTime . '" AND "' . $endTime . '"');
                                    }
                                  })->orderBy('i.id', 'desc')
                                    ->get();

            $returnArray = $objItemsDs->doArray($objResult);                        
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $returnArray;
    }

    /**
     * 
     * @param type $arrResult
     * @param type $itemsType
     * @return string
     */
    function formatItemsQueryResult($arrResult, $itemsType = '0') {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $arrResponse = $arrValue = [];
        try {

            if (!empty($arrResult)) {
                $objChannels = new ChannelsDataSource();
                $objItemsDs = new ItemsDataSource();
                $objExcel = new ExportData();
                $arrChannels = $objChannels->getItemsChannelsAdtypes();
                $headerType = ($itemsType == '1') ? 2 : 0;
                $arrHeaders = $objExcel->getSheetHeaders($eventID = 0, $intUsersId = 0, $headerType);
                unset($arrHeaders['acitivity']);
                if ($itemsType != '1') {
                    unset($arrHeaders['items_import_source']);
                }

                foreach ($arrResult as $val) {                    
                    unset($val['activity']);                    
                    $val['cost'] = ItemsUtils::formatPriceValues($val['cost']);
                    $val['base_unit_retail'] = ItemsUtils::formatPriceValues($val['base_unit_retail']);
                    if ($itemsType != '1') {
                        unset($val['items_import_source']);
                        $val['price_id'] = trim(strtoupper($val['price_id']));
                        $val['dotcom_price'] = ItemsUtils::formatPriceValues($val['dotcom_price']);
                        $val['advertised_retail'] = ItemsUtils::formatAdRetaliValue($val['advertised_retail']); //formatPriceValues($val['advertised_retail']);
                        $val['was_price'] = ItemsUtils::formatPriceValues($val['was_price']);
                        $val['save_amount'] = ItemsUtils::formatPriceValues($val['save_amount']);
                        $val['forecast_sales'] = ItemsUtils::formatPriceValues($val['forecast_sales']);
                        $val['made_in_america'] = ItemsUtils::setDefaultNoValuesCol('made_in_america', $val['made_in_america']);
                        $val['day_ship'] = ItemsUtils::setDefaultNoValuesCol('day_ship', $val['day_ship']);
                        $val['co_op'] = ItemsUtils::setDefaultNoValuesCol('co_op', $val['co_op']);
                        $val['landing_url'] = ItemsUtils::addhttp($val['landing_url']);
                        $val['landing_url'] = PiLib::isValidURL($val['landing_url']);
                        $val['item_image_url'] = PiLib::isValidURL($val['item_image_url']);
                        $val['no_of_linked_item'] = $this->getNoOfLinkedItemsByUpc($val['events_id'], $val['upc_nbr']);
                        $val['attributes'] = $objItemsDs->getAttributesSelectedValues($val['attributes']);
                        $val['local_sources'] = !empty($val['local_sources']) && ($val['local_sources'] != 'Yes') ? 'No - '. $val['local_sources'] : 'Yes';                        
                        $val['priority'] = !empty($val['priority']) ? $val['priority'] : '--';
                        //$val['mixed_column2'] = $this->getOmitVersionsByItemId($val['id']);
                        //$val['versions'] = $this->getVersionsByItemsId($val['id']);

                        $arrItems = array_merge($val, isset($arrChannels[$val['id']]) ? $arrChannels[$val['id']] : []);
                    } else {
                        $arrItems = $val;
                        $arrItems['items_import_source'] = ($arrItems['items_import_source'] == '0') ? 'IQS' : 'Import';
                    }

                    foreach ($arrHeaders as $key => $label) {
                        $arrValue[$val['id']]['is_excluded'] = ($arrItems['is_excluded'] == '1') ? true : false;
                        $arrValue[$val['id']]['ID'] = $val['id'];
                        $arrValue[$val['id']]['Event Name'] = $val['event_name'];                        
                        $arrValue[$val['id']][$label] = isset($arrItems[$key]) ? $arrItems[$key] : '';
                    }
                    unset($arrItems);
                }

                unset($arrResult, $arrChannels);
            }
            $arrResponse = array_values($arrValue);
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            $arrResponse = ['message' => $exMsg];
        }
        return $arrResponse;
    }

    /**
     * 
     * @param type $dirPath
     * @return boolean
     */
    function moveToSftpFolder($dirPath) {
        $status = false;
        try {
            $file_path = pathinfo($dirPath);
            $file_name = $file_path['filename'] . '.' . $file_path['extension'];
            $file_contents = file_get_contents($dirPath);
            Storage::disk('sftp')->put($file_name, $file_contents);
            $file_exists = Storage::disk('sftp')->exists($file_name);
            if ($file_exists) {
                $status = true;
            } else {
                if (file_exists($dirPath)) {
                    unlink($dirPath); // if file not move to sftp, from local genered file will be deleted.
                }
            }
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }

        return $status;
    }

    /**
     * 
     * @param type $intEventId
     * @param type $upcNbr
     * @return type
     */
    function getNoOfLinkedItemsByUpc($intEventId, $upcNbr){
        
        $objItems = new Items();
        $result = 0;
        if(!empty($upcNbr)){
            $result = $objItems->where('events_id', $intEventId)
                               ->where('upc_nbr', $upcNbr)
                               ->where('items_type', '1')
                               ->orderBy('id', 'asc')->limit(1)->count();
        }
        return $result;
        
    }
    
    function getOmitVersionsByItemId($intItemId) {
        $omitString = '';
        $objItemsPriceZone = new \CodePi\Base\Eloquent\ItemsPriceZones();
        $dbResult = $objItemsPriceZone->dbTable('ipz')
                                      ->join('price_zones as pz', 'pz.id', '=', 'ipz.price_zones_id')
                                       ->selectRaw('IF(pz.versions IS NOT NULL, CONCAT(\'OMIT: \', GROUP_CONCAT(pz.versions SEPARATOR \', \')), NULL) AS omitVersion')
                                      ->where('ipz.items_id', $intItemId)
                                      ->where('ipz.is_omit', '1')
                                      ->first();
        if($dbResult){
            $omitString = $dbResult->omitVersion;
        }
        return $omitString;
    }
    
    function getVersionsByItemsId($intItemId) {
        $arrVersions = [];
        $stringVersions = 'No Price Zone found.';
        $objItemsPriceZone = new \CodePi\Base\Eloquent\ItemsPriceZones();
        $dbResult = $objItemsPriceZone->dbTable('ipz')
                ->join('price_zones as pz', 'pz.id', '=', 'ipz.price_zones_id')
                ->selectRaw('CASE is_omit WHEN  \'0\' THEN GROUP_CONCAT(pz.versions SEPARATOR \', \') WHEN  \'1\' THEN CONCAT(\'OMIT: \',GROUP_CONCAT(pz.versions SEPARATOR \', \')) END AS versions')
                ->where('ipz.items_id', $intItemId)
                ->groupBy('is_omit')
                ->get()
                ->toArray();
        if (!empty($dbResult)) {
            foreach ($dbResult as $row) {
                $arrVersions[] = $row->versions;
            }
            asort($arrVersions);             
            if (count($arrVersions) > 1) {
                $stringVersions = $arrVersions[0] . ' ' . $arrVersions[1];
            } else {
                $stringVersions = $arrVersions[0];
            }
            unset($arrVersions);
        }
        return $stringVersions;
    }
    
    function currencyFormatColumn() {
        $objItemsDs = new ItemsDataSource();
        $arrHeaders = $objItemsDs->getItemDefaultHeaders($type = 0);
        $headers = [];
        foreach ($arrHeaders as $header) {
            if ($header['format'] == '1') {
                $headers[] = trim($header['name']);
            }
        }
        return $headers;
    }
    
}