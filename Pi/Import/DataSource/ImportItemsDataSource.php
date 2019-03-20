<?php

namespace CodePi\Import\DataSource;

use CodePi\Base\Import\ImportFiles;
use CodePi\Base\Import\ValidateFile;
use CodePi\ImportExportLog\Commands\ImportExportLog;
use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\Base\Commands\CommandFactory;
use Illuminate\Support\Facades\DB;
use CodePi\Base\Eloquent\Items;
#use CodePi\Import\Mailer\ImportMailer;
use CodePi\Base\Libraries\FileReader\ExcelReader;
use CodePi\Items\Commands\AddItems;
use CodePi\Base\Libraries\FileReader\ReaderFactory;
use CodePi\Items\DataSource\ItemsDataSource;
use phpseclib\Net\SFTP;
use CodePi\Base\Libraries\ZipFileFunctions;
use CodePi\Base\Libraries\Upload\UploadType;
use CodePi\Import\Utils\FileReadValidations;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Base\DataTransformers\DataSourceResponse;
use CodePi\ImportExportLog\Commands\ErrorLog;
use App\Events\IqsProgress;
class ImportItemsDataSource extends ImportFiles {

    private $sheetNo = 0;
    private $headerRow = 0;
    public $headers = FALSE;
    public $masterTable = 'items';
    public $mailMsg = '';

    function __construct() {

        $objValidate = new ValidateFile;
        $objValidate->setAllowedTypes(['xlsx', 'xls']);
        $objValidate->setSize(10 * 1024 * 1024);
        $objValidate->setContainer(storage_path('app/public') . '/Uploads/item_imports/');
        $objValidate->setMinLinesCount(1);
        $objValidate->setMaxLinesCount(1000000);
        $objValidate->setHeaders($this->headers);
        $objValidate->setSheetNo($this->sheetNo);
        $objValidate->setHeaderRow($this->headerRow);
        $objValidate->isSetMatchHeaders = FALSE;
        $objValidate->isSetMaxLinesCount = FALSE;
        $objValidate->isSetMinLinesCount = TRUE;
        $objValidate->isValidateHeaders = FALSE;
        $this->setObjValidate($objValidate);
    }

    /*     * *
     * Note: importHandler is abstract method of Import Files
     * @params File
     * @Description To read Data from file
     * @Returns array
     * Note: 
     */

    function importHandler($file) {
        return $this->importData($file);
    }

    /*     * *
     * @params Command
     * @Description: To read data from file 
     * @Returns array 
     */

    function importItems($command) {
        DefaultIniSettings::apply();
        $objStores = new Items();
        $objItemsDataSource = new ItemsDataSource;
        $data = $command->dataToArray();
        $otherColumn = [];
        $result = [];
        $fileData = $this->getReadExcelData($data['filename']); //$this->importHandler($data['filename']);
        
        $itemData = $ignored_items = [];

        if (is_array($fileData) && !empty($fileData)) {

            $fileDataWithoutHeaders = array_shift($fileData);

            if (!empty($fileDataWithoutHeaders)) {

                foreach ($fileDataWithoutHeaders as $key => $records) {
                    if (isset($records[0]) && !empty($records[0])) {
                        if (is_numeric($records[0])) {
                            $itemData['item_number'][] = $records[0];
                            unset($records[0]);
                            $otherColumn = $records;
                        } else {
                            $ignored_items[] = $records[0];
                        }
                    }
                }
                
                if (!empty($otherColumn)) {
                    $result = ['status' => false, 'message' => 'Additional data found in the file. Only column 1 data will be uploaded. Would you like to go ahead?.'];
                } else
                if (!empty($itemData)) {

                    $itemData['items'] = array_values(array_unique($itemData['item_number']));
                    unset($itemData['item_number']);
                    $command->items = $itemData['items'];
                    $data['items'] = $itemData['items'];

                    $respnse = $objItemsDataSource->saveItems($command);
                    $result = ['status' => true, 'message' => 'File uploaded successfully', 'ignored_items' => $ignored_items];
                    $result = array_merge($result, $respnse);
                } else {
                    $result = ['status' => false, 'message' => 'No data found on Column 1 of the uploaded file.'];
                }
            }
        } else {
            $result = ['status' => false, 'message' => $fileData];
        }

        $importLogDet = ['data' => $data, 'response' => $result, 'filename' => substr($this->fileName, strrpos($this->fileName, '/') + 1), 'table' => 'Items'];
        $logRes = $this->saveImportLog($importLogDet);
        $result['importId'] = $logRes;
        return $result;
    }

    /**
     * To Save the Import Log informations
     * @param type $data
     * @return boolean
     */
    function saveImportLog($data) {
        $logData['action'] = 'import_items';
        $logData['params'] = $data['data'];
        $logData['response'] = $data['response'];
        $logData['message'] = $data['response']['message'];
        $logData['master_id'] = $data['data']['event_id'];
        $logData['master_table'] = $data['table'];
        $logData['filename'] = $data['filename'];
        $importCmd = new ImportExportLog($logData);
        $response = CommandFactory::getCommand($importCmd);
        if (!empty($response)) {
            return $response->id;
        } else {
            return FALSE;
        }
    }

//    function getData($file, $delimeter) {        
//        DefaultIniSettings::apply();
//        $objReader = ReaderFactory::select($file, $delimeter);
//        $objReader->setSettings($this->getObjValidate());
//        return $objReader->getData($file, true);
//    }

    /**
     * Upload files
     * 
     * @param object $command
     * @return array
     */
    function uploadItemsFile($command) {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        try {
            $result = [];
            $status = false;
            $params = $command->dataToArray();
            $upload = $this->getUploadFactory($command);
            $upload->setSize($params['size']);
            $upload->setAllowedTypes($params['extensions']);
            $upload->setContainer(storage_path('app/public') . '/Uploads/item_imports/');
            $tmpfile = $upload->save();
            
            if (isset($tmpfile['error']) && $tmpfile['error'] == 'success') {
                $objFileRead = new FileReadValidations();
                $objFileRead->setFiles($tmpfile['filename']);
                $result = $objFileRead->validate(); 
                $status = true;
            }
            
            if (isset($result['error']) && $result['error'] != 'success') {
                $tmpfile['error'] = $result['error'];   
                $status = false;
            }
            
            if(isset($result['inputValues']) && !empty($result['inputValues'])){
                $status = true;
                
            }
            $TotalValues = isset($result['TotalValues']) ? $result['TotalValues'] : 0;
            $duplicateValues = isset($result['duplicateValues']) ? $result['duplicateValues'] : 0;
            $ignoredValues = isset($result['ignoredValues']) ? $result['ignoredValues'] : 0;
            
            $tmpfile['fileData'] = array('TotalValues' => $TotalValues, 'duplicateValues' => $duplicateValues, 'ignoredValues' => $ignoredValues);
                        
        } catch (\Exception $ex) {
            $tmpfile['error'] = 'fail';
            $status = false;
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();            
            CommandFactory::getCommand(new ErrorLog(array('message' => $exMsg)), TRUE);
        }
        $tmpfile['status'] = $status;
        return $tmpfile;
    }

    /**
     * Find the upload method
     * 
     * @param object $command
     * @return object
     */
    function getUploadFactory($command) {
        $params = $command->dataToArray();
        if ($_FILES['file']['tmp_name']) {
            $upload = UploadType::Factory('Regular');
            $files = $_FILES['file'];
        } else {
            if (!empty($_SERVER ['HTTP_X_FILE_NAME'])) {
                $files = $_SERVER ['HTTP_X_FILE_NAME'];
            } else {
                $files = $_REQUEST [$params['filename']];
            }
            $upload = UploadType::Factory('Stream');
        }

        $upload->setFiles($files);
        return $upload;
    }

    function iqsImportItems($command) {


        $params = $command->dataToArray();
//		 echo "Datasource";
//		 print_r($params);die;
        $response = [];

        $objItemsDataSource = new ItemsDataSource;
        //   try{
        if (!empty($command->filename)) {
            
            $response = $this->importItems($command);
        } else {
            if (isset($params['items']) && !empty($params['items'])) {
                
                $response = $objItemsDataSource->saveItems($command);
            }
        }

        if (isset($response['items_id']) && !empty($response['items_id'])) {

            $data['items_id'] = array_filter($response['items_id']);
            $data['event_id'] = PiLib::piEncrypt($command->event_id);
            $objCommand = new GetItemsList($data);
            $cmdResponse = CommandFactory::getCommand($objCommand);
            $response['status'] = true;
            $response = array_merge($cmdResponse['items'], $response);
//             $objCopyDs = new CopyDs();
//        $dataParent['items_id'] = $response['items_id'];
//        $dataParent['event_id'] = $command->events_id;
//        $returnResult['objResult'] = $objCopyDs->getItemListById($dataParent);
//        
//        $objGridResponse = new ItemsGridDataResponse();
//        $arrResponse = $objGridResponse->getGridResponse($returnResult, $command);
//        $arrResponse['event_id'] = PiLib::piEncrypt($command->events_id);
//        
//        if ($arrResponse) {
//            broadcast(new ItemActions($arrResponse, 'import'))->toOthers();
//        }
//        $response= $arrResponse;
            //unset($response['items_id']);
        } else {
            $response['event_id'] = $command->event_id;
            $response['status'] = false;
            /**
             * Need to add  more descriptive error messages
             */
            $response['message'] = isset($params['items']) && empty($params['items']) ? 'Please enter valid search numbers' : 'Failure to add items. Please try agian..!';
            $users_id = isset($command->users_id) ? $command->users_id : $command->last_modified_by;
            $arrEventData = ['users_id' => $users_id, 'events_id' => PiLib::piEncrypt($response['event_id']), 'status' => true, 'error' => true, 'message' => $response['message']];
            event(new IqsProgress($arrEventData));
        }
        
        return $response;
    }

    /**
     * Read import excel files
     * @param type $file
     * @return array
     */
    function getReadExcelData($file) {
        DefaultIniSettings::apply();
        $objReader = ReaderFactory::select($file);
        $fileData = $objReader->getData($file, true);        
        return $fileData;
    }

}
