<?php

namespace CodePi\Export\DataSource;

use CodePi\Base\Eloquent\Events;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Export\DataSource\ExportData;
use CodePi\Items\DataSource\ItemsDataSource;
use ZipArchive;
use CodePi\Export\DataSource\ExportItemsExcel;

class ExportCsv {
    /**
     * 
     * @param type $command
     * @return types
     */
    function export($command) {
        DefaultIniSettings::apply();
        $fileList = $exportResponse = [];
        try {
            $command->exportSheetIndex = 0;
            $objExportData = new ExportData($command);
            $resultItems = $objExportData->getData();

            $command->exportSheetIndex = 1;
            $objExportData = new ExportData($command);
            $linkItems = $objExportData->getData();
            $arrItems = ['result' => $resultItems/*, 'link' => $linkItems*/];
            unset($resultItems, $linkItems);
            $objExportItems = new ExportItemsExcel(null, $command, null, null, null);
            foreach ($arrItems as $key => $csvdata) {
                if ($csvdata) {
                    //$sheetName = ($key == 'result') ? 'ResultItems' : 'LinkedItems';
                    //$fileName = $sheetName . ' ' . date('m-d-Y h_i_s A') . '.csv';
                    $filename = $objExportItems->getEventName() . ' ' . date('m-d-Y h_i_s A') . '.csv';
                    $dirPath = storage_path('app') . '/public/Export/export_items/' . $filename;
                    $fileList[] = $dirPath;
                    $fp = fopen($dirPath, 'w');
                    $headers = isset($csvdata[0]) ? array_keys($csvdata[0]) : [];
                    if (isset($headers[0])) {
                        unset($headers[0]);
                    }

                    fputcsv($fp, $headers);
                    $currencyFormat = $objExportItems->getCurrencyFormatColumns();
                    foreach ($csvdata as $fields) {
                        if ($fields['is_excluded'] == true && $command->exportSheetIndex == '0') {
                            $fields['Ad Block'] = 'ZDELETE';
                        }
                        unset($fields['is_excluded']);
                        foreach ($fields as $key => $value) {
                            if (in_array($key, $currencyFormat)) {
                                $fields[$key] = !empty($value) ? '$ ' . floatval($value) : $value;
                            }
                        }
                        fputcsv($fp, $fields);
                    }
                    sleep(2);
                    if (file_exists($dirPath)) {
                        chmod($dirPath, 0777);
                    }
                    fclose($fp);
                }
            }
            unset($arrItems, $currencyFormat);

//            $destination = storage_path('app') . '/public/Export/export_items/Sample.zip';
//            $zip = $this->createZip($fileList, $destination);
//            if (!empty($zip)) {
//                if (!empty($fileList)) {
//                    unlink($fileList[0]);
//                    unlink($fileList[1]);
//                }
//            }

            $exportResponse = ['status' => true, 'filename' => PiLib::piEncrypt($filename)];            
            $objExportItems->saveExportLog($exportResponse, true);
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            $exportResponse = ['status' => false, 'filename' => null, 'message' => $exMsg];
        }

        return $exportResponse;
    }

    /**
     * 
     * @param type $files
     * @param type $destination
     * @param type $overwrite
     * @return boolean
     */
    function createZip($files = array(), $destination = '', $overwrite = false) {

        if (file_exists($destination) && !$overwrite) {
            return false;
        }

        $validFiles = [];
        if (is_array($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $validFiles[] = $file;
                }
            }
        }

        if (count($validFiles)) {
            $zip = new ZipArchive();
            if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                return false;
            }

            foreach ($validFiles as $file) {
                $zip->addFile($file, $file);
            }

            $zip->close();
            return file_exists($destination);
        } else {
            return false;
        }
    }

}
