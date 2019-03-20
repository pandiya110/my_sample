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
use CodePi\Base\Eloquent\ActivityLogs;
use CodePi\Base\Eloquent\MasterDataOptions;
use DateTime,
    DateTimeZone,
    DateInterval;
use ZipArchive;

class ExportFlatItemsSftpDs {
    /**
     *
     * @var type 
     */
    public $addFlag = array('insert', 'copy', 'move', 'duplicate');
    /**
     *
     * @var type 
     */
    public $updateFlag = array('update','activated','sync', 'publish', 'unpublish');
    
    /**
     * 
     * @param type $command
     * @return boolean
     */
     function getItemsDataToExport($params) {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $reqType = isset($params['requireType']) ? $params['requireType'] : 1;
        $itemsType = $params['itemsType'];
        $response = [];
        $objExport = new ExportData();
        $objSystemLog = new SystemsLogs();
        try {
            $return = $this->getAllItemsFromDB($reqType, $itemsType, $params['cronTime']);
            $arrItems = $this->formatItemsQueryResult($return, $itemsType);
            $fileName = 'TRACK_TAB_PROJECT_' . date('Ymd') . '_' . str_replace(":", "", $params['cronTime']) . '.csv';
            $dirPath = storage_path('app') . '/public/Export/export_flat_items_to_sftp/' . $fileName;
            $fp = fopen($dirPath, 'w');
            $headers = isset($arrItems[0]) ? array_keys($arrItems[0]) : array_values($this->getFlatfileHeaders());
            $hdr = [];
            $hdr_flds = [];
            $hdr[] = implode('|', $headers);
            //fputcsv($fp, $hdr, '|', " ");
            $this->my_fputcsv($fp, $hdr, ',', '');
            unset($hdr);
            $currencyFormat = $this->currencyFormatColumn();
            foreach ($arrItems as $fields) {
                foreach ($fields as $key => $value) {
                    $value = str_replace(array("\r\n", "\r", "\n"), array("", "", ""), $value);
                    $fields[$key] = $value;
                    if (in_array($key, $currencyFormat)) {
                        $value = str_replace('"', '', $value);
                        $fields[$key] = !empty($value) ? '$ ' . floatval($value) : $value;
                    }
                }

                $hdr_flds[] = implode('|', $fields);
                $this->my_fputcsv($fp, $hdr_flds, ',', '');
                //fputcsv($fp, $hdr_flds, '|', " ");
                unset($hdr_flds);
            }
            sleep(2);
            if (file_exists($dirPath)) {
                chmod($dirPath, 0777);
                $objSystemLog->saveRecord(['action' => 'ExportToSftp',
                    'master_id' => 0,
                    'filename' => $fileName,
                    'message' => $itemsType . ' - File has been generate successfully']
                );
            }
            fclose($fp);
            $response = ['status' => true, 'dirPath' => $dirPath];
        } catch (\Exception $ex) {
            $response = ['status' => false, 'message' => $ex->getMessage() . ' ' . $ex->getFile() . ' ' . $ex->getLine()];
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $response;
    }

    /**
     * 
     * @param type $handle
     * @param type $fieldsarray
     * @param type $delimiter
     * @param type $enclosure
     * @return type
     */
    function my_fputcsv($handle, $fieldsarray, $delimiter = "~", $enclosure = '') {
        $glue = $enclosure . $delimiter . $enclosure;
        return fwrite($handle, $enclosure . str_replace('"', '',implode($glue, $fieldsarray)) . $enclosure . "\r\n");
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
            $column[] = 'i.last_modified as last_updated';
            $columnName = implode($column, ',');
            unset($column);
            $startTime = $endTime = null;
            $objItems = new Items();
            if ($reqType == 2) {
                //$startTime = date('Y-m-d H:i:s');
                //$endTime = date('Y-m-d H:i:s');
                //$inputDate = date('Y-m-d') . ' ' . $cronTime;                 
                $endTime = $this->date_convert(date('Y-m-d') . ' ' . $cronTime, 'CST', 'UTC', 'Y-m-d H:i:s');                
                if ($cronTime == '06:00') {
                    $date = new DateTime($endTime);
                    $date->sub(new DateInterval('PT10H'));
                    $startTime = $date->format('Y-m-d H:00:00');
                } else if ($cronTime == '11:00') {
                    $date = new DateTime($endTime);
                    $date->sub(new DateInterval('PT5H'));
                    $startTime = $date->format('Y-m-d H:00:00');
                } else if ($cronTime == '16:00') {
                    $date = new DateTime($endTime);
                    $date->sub(new DateInterval('PT5H'));
                    $startTime = $date->format('Y-m-d H:00:00');
                } else if ($cronTime == '20:00') {
                    $date = new DateTime($endTime);
                    $date->sub(new DateInterval('PT4H'));
                    $startTime = $date->format('Y-m-d H:00:00');
                }
            }
            //\DB::enableQueryLog();
            //echo $startTime.'-'.$endTime; exit; 
            $objResult = $objItems->dbTable('i')
                                  ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                                  ->join('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                                  ->join('events as e', 'e.id', '=', 'i.events_id')
                                  ->select('i.id', 'e.name as event_name', 'is_excluded', 'i.events_id', 'i.items_import_source', 'i.tracking_id')
                                  ->selectRaw($columnName)
                                  ->where('e.is_draft', '0')
                                  ->where('items_type', $itemsType)
                                  ->where('i.is_no_record', '0')
                                  ->where('e.campaigns_id', '!=', '0')
                                  ->where('e.campaigns_projects_id', '!=', '0')
                                  ->where(function ($query) use ($reqType, $startTime, $endTime) {
                                  if ($reqType != 1) {
                                    //$query->whereRaw('DATE(i.last_modified) = DATE(NOW())');
                                   $query->whereRaw('i.last_modified BETWEEN "' . $startTime . '" AND "' . $endTime . '"');
                                   //$query->whereRaw('i.last_modified BETWEEN "2019-02-01 08:40:29 " AND "2019-02-28 08:40:29"');
                                  }
                                  })->orderBy('i.id', 'desc')//->limit(50) 
                                    ->get();
            //$query = \DB::getQueryLog();dd($query);
            $returnArray = $objItemsDs->doArray($objResult);
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $returnArray;
    }
    /**
     * 
     * @param type $time
     * @param type $oldTZ
     * @param type $newTZ
     * @param type $format
     * @return type
     */
    function date_convert($time, $oldTZ, $newTZ, $format) {

        $d = new DateTime($time, new DateTimeZone($oldTZ));
        $d->setTimezone(new DateTimeZone($newTZ));
        return $d->format($format);
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
        $arrItems = $arrResponse = $arrValue = [];
        try {

            if (!empty($arrResult)) {
                $objChannels = new ChannelsDataSource();
                $objItemsDs = new ItemsDataSource();
                $objMasterDataOptions = new MasterDataOptions();
                $objActivityLogs = new ActivityLogs;
                $arrChannels = $objChannels->getItemsChannelsAdtypes();
                $arrHeaders = $this->getFlatfileHeaders();
                $arrMasterData = [];
                $masterInfo = $objMasterDataOptions->get(['id', 'name'])->toArray();
                if (!empty($masterInfo)) {
                    foreach ($masterInfo as $key => $value) {
                        $arrMasterData[$value['id']] = $value['name'];
                    }
                }
                unset($arrHeaders['acitivity']);
                if ($itemsType != '1') {
                    unset($arrHeaders['items_import_source']);
                }

                foreach ($arrResult as $val) {
                    $val = $objItemsDs->filterStringDecode($val);                       
                    $arrItems = [];
                    $trackingId = isset($val['tracking_id']) && !empty($val['tracking_id']) ? $val['tracking_id'] : 0;
                    $activityResp = $objActivityLogs->where('events_id', $val['events_id'])
                                                    ->where('type', '0')
                                                    ->where('tracking_id', $trackingId)
                                                    ->orderBy('id', 'DESC')->limit(1)->get(['date_added as last_updated', 'actions as record_flag'])->toArray();
//                    if (!empty($activityResp)) {                        
//                        $val['record_flag'] = isset($activityResp[0]['record_flag']) ? $activityResp[0]['record_flag'] : '';
//                        if (strtolower($val['record_flag']) == 'insert') {
//                            $val['record_flag'] = 'Add';
//                        }
//                        if (strtolower($val['record_flag']) == 'update') {
//                            $val['record_flag'] = 'Update';
//                        }
//                        if (strtolower($val['record_flag']) == 'excluded') {
//                            $val['record_flag'] = 'ZDelete';
//                        }
//                        if (strtolower($val['record_flag']) != 'add' && strtolower($val['record_flag']) != 'update' && strtolower($val['record_flag']) != 'zdelete') {
//                            $val['record_flag'] = '--';                            
//                        }
//                    }
                    if (!empty($activityResp)) {
                        $val['record_flag'] = isset($activityResp[0]['record_flag']) ? $activityResp[0]['record_flag'] : '';
                        if (in_array(strtolower($val['record_flag']), $this->addFlag)) {
                            $val['record_flag'] = 'Add';
                        }
                        if (in_array(strtolower($val['record_flag']), $this->updateFlag)) {
                            $val['record_flag'] = 'Update';
                        }
                        if (strtolower($val['record_flag']) == 'excluded') {
                            $val['record_flag'] = 'ZDelete';
                        }

                        if (strtolower($val['record_flag']) != 'add' && strtolower($val['record_flag']) != 'update' && strtolower($val['record_flag']) != 'zdelete') {
                            $val['record_flag'] = '--';
                        }
                    } else {
                        $val['record_flag'] = '--';
                    }

                    unset($val['activity']);
                    $val['cost'] = ItemsUtils::formatPriceValues($val['cost']);
                    $val['base_unit_retail'] = ItemsUtils::formatPriceValues($val['base_unit_retail']);
                    if (isset($val['last_updated']) && !empty($val['last_updated'])) {
                        $val['last_updated'] = $this->date_convert($val['last_updated'], 'UTC', 'CST', 'Y-m-d H:i:s');
                    }
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
                        $val['priority'] = !empty($val['priority']) ? $val['priority'] : '--';
                        $val['advertised_item_description'] = preg_replace("/\r|\n/", " ", $val['advertised_item_description']);
                        $val['facing_brand_logo_bug'] = preg_replace("/\r|\n/", " ", $val['facing_brand_logo_bug']);
                        $val['trcnbr_vsi_fname_lctn'] = preg_replace("/\r|\n/", " ", $val['trcnbr_vsi_fname_lctn']);

                        $localSourcesVal = '';
                        if (!empty($val['local_sources'])) {
                            $localSources = explode(':', $val['local_sources']);
                            if (!empty($localSources) && isset($localSources[1])) {
                                $localSourcesVal = isset($arrMasterData[$localSources[1]]) ? ' - ' . $arrMasterData[$localSources[1]] : '';
                            }
                        }
                        $val['local_sources'] = !empty($localSourcesVal) && ($val['local_sources'] != 'Yes') ? 'No ' . $localSourcesVal : 'Yes';

                        if (!empty($val['gtin_nbr']) && !empty($val['upc_nbr']) && !empty($val['aprimo_campaign_id']) && !empty($val['aprimo_project_id']) && !empty($val['last_updated']) && !empty($val['record_flag'])) {

                            $arrItems = array_merge($val, isset($arrChannels[$val['id']]) ? $arrChannels[$val['id']] : []);
                            $arrItems['items_import_source'] = (isset($val['items_import_source']) && $val['items_import_source'] == '0') ? 'IQS' : 'Import';
                        }
                    } else {
                        if (!empty($val['gtin_nbr']) && !empty($val['upc_nbr']) && !empty($val['aprimo_campaign_id']) && !empty($val['aprimo_project_id']) && !empty($val['last_updated']) && !empty($val['record_flag'])) {
                            $arrItems = $val;
                            $arrItems['items_import_source'] = (isset($val['items_import_source']) && $val['items_import_source'] == '0') ? 'IQS' : 'Import';
                        }
                    }
                    if (!empty($arrItems)) {
                        foreach ($arrHeaders as $key => $label) {
                            $arrValue[$val['id']][$label] = isset($arrItems[$key]) ? $arrItems[$key] : '';
                        }
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
//        echo $dirPath;die;
        $status = false;
        try {
            $file_path = pathinfo($dirPath);
            $file_name = $file_path['filename'] . '.' . $file_path['extension'];
            $file_contents = file_get_contents($dirPath);
            Storage::disk('sftp1')->put($file_name, $file_contents);
            $file_exists = Storage::disk('sftp1')->exists($file_name);
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
    function getNoOfLinkedItemsByUpc($intEventId, $upcNbr) {

        $objItems = new Items();
        $result = 0;
        if (!empty($upcNbr)) {
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
        if ($dbResult) {
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
    /**
     * Flat file default headers
     * @return type
     */
    function getFlatfileHeaders(){
         return [
                    "gtin_nbr" => 'GTIN_Nbr',
                    "upc_nbr" => 'UPC_Nbr',
                    "ad_block" => 'AD_BLOCK',
                    "aprimo_campaign_id" => 'APRIMO_CAMPAIGN_ID',
                    "aprimo_campaign_name" => 'APRIMO_CAMPAIGN_NAME',
                    "aprimo_project_id" => 'APRIMO_PROJECT_ID',
                    "aprimo_project_name" => 'APRIMO_PROJECT_NAME',
                    "merchant_name" => 'SAMPLE_COORDINATOR_NAME', 
                    "merchant_email" => 'SAMPLE_COORDINATOR_EMAIL',
                    "supplier_contact_name" => 'SAMPLE_SUPPLIER_CONTACT_NAME',
                    "supplier_contact_email" => 'SAMPLE_SUPPLIER_CONTACT_EMAIL',
                    "local_sources" => 'VENDOR_SAMPLE_IND',
                    "trcnbr_vsi_fname_lctn" => 'ALT_SAMPLE_ACQUISITION',
                    "advertised_item_description" => 'ADVERTISED_ITEM_DESCRIPTION',
                    "page" => 'PAGE',
                    "priorarrResultity" => 'PRIORITY_ITEM',
                    "color_r_flarank" => 'COLOR_RANKING',
                    "facing_brand_logo_bug" => 'CFC_BM',
                    "logo_bug_details" => 'LOGO_DTLS',
                    "last_updated" => 'LAST UPDATE',
                    "record_flag" => 'RECORD_FLAG',
                    "id" => 'LB_ID',
                ];
    }

}