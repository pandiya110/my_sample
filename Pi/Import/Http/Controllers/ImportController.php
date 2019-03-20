<?php

namespace CodePi\Import\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use CodePi\Import\Commands\ImportItems;
use CodePi\Import\Commands\UploadItems;
use CodePi\Import\Commands\ImportMasterItems;
use CodePi\Import\Commands\UploadBulkItemsFile;
use CodePi\Import\Commands\ImportBulkData;


/**
 * @access public
 
 */
class ImportController extends PiController {

    /**
     * Import the the Items
     * 
     * @param Request $request
     * @return Response
     */    
    public function importAddItems(Request $request) {
        $data = $request->all();
        
        switch ($data['type']) {
            case 1 :
                $data['search_key'] = 'searched_item_nbr';
                break;
            case 2 :
                $data['search_key'] = 'upc_nbr';
                break;
            case 3 :
                $data['search_key'] = 'fineline_number';
                break;
            case 4 :
                $data['search_key'] = 'plu_nbr';
                break;
            case 5 :
                $data['search_key'] = 'itemsid';
                break;
            default:
                $data['search_key'] = 'searched_item_nbr';
        }
        /**
         * Find and replace the alphabet value from search values, allow only number to search the items
         */
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $value) {
                $orignalString = $value;
                $formatString = preg_replace("/[^0-9,.]/", "", $orignalString);
                if (strlen(trim($orignalString)) == strlen(trim($formatString))) {
                    $items[] = $orignalString;
                }
            }
        }
        $data['items'] = ($items) ? $items : [];   
        
        $command = new ImportItems($data);
        return $this->run($command, trans('Import::messages.success_uploadImport'), trans('Import::messages.failure_uploadImport'));
    }

    /**
     * This is test sftp import
     * 
     * @param Request $request
     * @return type
     */
//    public function test(Request $request){
//        $data = $request->all();
//        $command = new ImportItems($data);
//        return $this->run($command, trans('Import::messages.success_uploadImport'), trans('Import::messages.failure_uploadImport'));
//    }
    
    /**
     * File upload for Items
     * 
     * @param Request $request
     * @return Reponse
     */
    public function uploadItems(Request $request){
        $data = $request->all();
        $command = new UploadItems($data);
        return $this->run($command, trans('Import::messages.success_uploadImport'), trans('Import::messages.failure_uploadImport'));
    }
    
    /**
     * Import Master Items
     * @param Request $request
     * @return Response
     */
    public function importMasterItems(Request $request){
        $data = $request->all();
        $command = new ImportMasterItems($data);
        return $this->run($command, trans('Import::messages.success_uploadImport'), trans('Import::messages.failure_uploadImport'));
    }
    /**
     * Upload the bulk import items file
     * @param Request $resquest
     * @return Response
     */
    public function uploadBulkItemsFile(Request $resquest){
        $data = $resquest->all();
        $command = new UploadBulkItemsFile($data);
        return $this->run($command, trans('Import::messages.success_uploadImport'), trans('Import::messages.failure_uploadImport'));
    }
    /**
     * Import items will be added through back-end process
     * @param Request $request
     * @return Response
     */
    public function bulkImportItems(Request $request){
        $data = $request->all();
        $command = new ImportBulkData($data);
        return $this->run($command, trans('Import::messages.success_uploadImport'), trans('Import::messages.failure_uploadImport'));
        
    }
    
}


