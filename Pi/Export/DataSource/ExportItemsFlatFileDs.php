<?php

namespace CodePi\Export\DataSource;

use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Base\Eloquent\Items;
#use CodePi\Base\Libraries\DefaultIniSettings;
use ZipArchive;
use CodePi\Export\DataSource\ExportItemsSftpDs as ExportSftp;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;

class ExportItemsFlatFileDs {

    /**
     * Export Flat files and convert into zip files
     * @param type $params
     * @return boolean
     */
    function exportFlatFile($params) {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $response = $localFile = $zipFolderName = [];
        try {
            $array = array('0', '1');
            foreach ($array as $value) {
                $objExportSftp = new ExportSftp();
                $return = $this->getAllItemsFromDB($value);
                $arrItems = $objExportSftp->formatItemsQueryResult($return, $value);
                if (!empty($arrItems)) {
                    $resultType = ($value == '0') ? 'ResultItems' : 'LinkedItems';
                    $fileName = 'ListBuilder_' . $resultType . '_' . date('mdYHis') . rand(10, 100) . '.csv';
                    $dirPath = storage_path('app') . '/public/Export/export_flat_items_to_sftp/' . $fileName;
                    $localFile[$fileName] = $dirPath;
                    $fp = fopen($dirPath, 'w');
                    $headers = isset($arrItems[0]) ? array_keys($arrItems[0]) : [];
                    if (isset($headers[0])) {
                        unset($headers[0]);
                    }

                    fputcsv($fp, $headers, '|');
                    $currencyFormat = $objExportSftp->currencyFormatColumn();
                    foreach ($arrItems as $fields) {
                        if ($fields['is_excluded'] == true && $value == '0') {
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
                    }
                    fclose($fp);
                }
            }
            /**
             * Create Zip file
             */
            if (!empty($localFile)) {
                $zipFolderName = 'listbuilder' . md5(mt_rand() . time()) . '.zip';
                $zip = new ZipArchive();
                $source = storage_path('app') . '/public/Export/export_items_to_zip/' . $zipFolderName;
                $zip->open($source, ZipArchive::CREATE);
                if (!empty($localFile)) {
                    foreach ($localFile as $key => $file) {
                        $downloadFile = file_get_contents($file);
                        $zip->addFromString($key, $downloadFile);
                    }
                    if (file_exists($zipFolderName)) {
                        chmod($zipFolderName, 0777);
                    }

                    $zip->close();
                    /**
                     * Delete the files from temp directory
                     */
                    foreach ($localFile as $key => $file) {
                        if (file_exists($file)) {
                            unlink($file);
                        }
                    }
                }
                $response = ['status' => true, 'dirPath' => $zipFolderName, 'message' => 'Successfully exported as a zip file'];
            } else {
                $response = ['status' => false, 'dirPath' => $zipFolderName, 'message' => 'Files are not available to export'];
            }
        } catch (\Exception $ex) {
            $response = ['status' => false, 'message' => $ex->getMessage() . ' ' . $ex->getFile() . ' ' . $ex->getLine()];
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            throw new DataValidationException($exMsg, new MessageBag());
        }

        return $response;
    }

    /**
     * 
     * @param type $reqType
     * @param type $itemsType
     * @return type
     */
    function getAllItemsFromDB($itemsType = '0') {
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
            $objItems = new Items();
            
            $objResult = $objItems->dbTable('i')
                                  ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                                  ->join('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                                  ->join('events as e', 'e.id', '=', 'i.events_id')
                                  ->select('i.id', 'e.name as event_name', 'is_excluded', 'i.events_id', 'i.items_import_source')
                                  ->selectRaw($columnName)
                                  ->where('e.is_draft', '0')
                                  ->where('items_type', $itemsType)
                                  ->where('i.is_no_record', '0')
                                  ->where(function ($query) use ($itemsType) {
                                     $query->whereRaw('DATE(i.last_modified) = DATE(NOW())');
                                  })->orderBy('i.id', 'desc')
                                    ->get();
            
            $returnArray = $objItemsDs->doArray($objResult);
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            throw new DataValidationException($exMsg, new MessageBag());
        }
        return $returnArray;
    }

}
