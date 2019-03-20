<?php

namespace CodePi\Import\DataSource;


use CodePi\ImportExportLog\Commands\ImportExportLog;
use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\Base\Commands\CommandFactory;
use Illuminate\Support\Facades\DB;
use CodePi\Base\Eloquent\Items;
#use CodePi\Base\Libraries\FileReader\ExcelReader;
#use CodePi\Base\Libraries\FileReader\ReaderFactory;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Base\Libraries\Upload\UploadType;
use CodePi\Base\Libraries\PiLib;
#use CodePi\Base\DataTransformers\DataSourceResponse;
use CodePi\Import\Utils\BulkImportFileReadValidations;
use CodePi\Base\Eloquent\ItemsHeaders;
use App\Events\BulkItemsImportProcess;
use CodePi\Base\Eloquent\ItemsEditable;
use CodePi\Base\Eloquent\ItemsNonEditable;
use App\Events\ItemActions;
#use CodePi\Items\Commands\GetItemsList;
use CodePi\ItemsActivityLog\Logs\ActivityLog;
use CodePi\Base\Eloquent\MasterDataOptions;
use CodePi\Items\DataSource\PriceZonesDataSource;
use CodePi\Base\Eloquent\PriceZones;
use CodePi\Events\DataSource\EventsDataSource;
use CodePi\Items\DataSource\CopyItemsDataSource as CopyDs;
use CodePi\Items\Utils\ItemsGridDataResponse;
use CodePi\Items\DataSource\LinkedItemsDataSource;
use CodePi\Items\Utils\LinkedItemsGridDataResponse;
use CodePi\Base\Eloquent\MasterItems;


class BulkImportItemsDs {

    public $sheetNo;
    private $unique_id;

    function __construct() {
        $this->unique_id = mt_rand() . time();
    }
    /**
     * 
     * @param array $params
     * @param $params['size'] Description
     * @param $params['extensions'] Description
     * @param $params['file'] Description
     * @command ImportExportLog To add the upload files logs     
     * @return array
     */
    function uploadBulkItemsFile($params) {
        try {
            
            DefaultIniSettings::apply();
            $status = false;
            $tmpfile = array();
            $upload = $this->getUploadFactory($params);
            $upload->setSize($params['size']);
            $upload->setAllowedTypes($params['extensions']);
            $upload->setContainer(storage_path('app/public') . '/Uploads/bulk_items_import/');
            
            $tmpfile = $upload->save();
            
            if (isset($tmpfile['error']) && $tmpfile['error'] == 'success') {
                $status = true;
                $file = isset($tmpfile['filename']) ? $tmpfile['filename'] : '';
                $fileError = $this->bulkImportReadFileValidations($file);
                
                if (isset($fileError['error']) && $fileError['error'] != 'success') {
                    $tmpfile['error'] = $fileError['error'];
                    $status = false;
                }
            }  
            
            $message = isset($tmpfile['error']) ? $tmpfile['error'] : '';
        } catch (\Exception $ex) {
            echo $message = $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();exit;
        }

        $objCommand = new ImportExportLog(array('action' => 'UploadBulkImportFiles', 'data' => $params, 'response' => $tmpfile, 'filename' => isset($tmpfile['filename']) ? $tmpfile['filename'] : '', 'message' => $message));
        $response = CommandFactory::getCommand($objCommand);
        $tmpfile['status'] = $status;

        return $tmpfile;
    }

    /**
     * Class UploadType::Factory [Find the Upload Factory method]
     * @param array $params
     * @return Object of Upload class 
     */
    function getUploadFactory($params) {

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

    /**
     * Set Default Headers to Compare the Export file headers
     * @return array
     */
    function importHeaders() {
        return ['Page',
            'Ad Block',
            'Rank',
            'Theme',
            'Searched Item Nbr',
            'Link Type',
            'Result Item Nbr',
            'SBU',
            'Acctg Dept Nbr',
            'Dept Description',
            'Category Description',
            'UPC Nbr',
            'Fineline Number',
            'Season Year(Apparel Only)',
            'PLU Nbr',
            'Item Status Code',
            'Item File Description',
            'Signing Description',
            'Short Version Description',
            'Advertised Item Description',
            'Size',
            'Cost',
            'Base Unit Retail',
            'Advertised Retail',
            'Price ID',
            'Was Price',
            'Bonus Details',
            'New, Exclusive, and/or Made in America?',
            'Store Count',
            'On Feature',
            'Grouped Item',
            'Line List Item',
            'Co-op?',
            'Versions',
            'Forecast Sales $',
            'Buyer User ID',
            'Sr Merchant',
            'Planner',
            'Pricing Mgr',
            'Repl Manager',
            'Supplier',
            'Supplier Nbr',
            'Brand Name',
            //'Will you submit a Sample, Purchase Form or Vendor Supplied Image?',
            'If VSI or Existing Image, enter file name and location',
            'Color/Flavor Ranking',
            'Customer Facing Copy / Brand Mandatories',
            'Logo / Bug Details',
            'Each'
        ];
    }
    /**
     * Set Linked Items Headers
     * @return type
     */
    function importLinkedItemsHeaders() {
//        return [
//            'item_file_description' => 'Item File Description',
//            'cost' => 'Cost',
//            'base_unit_retail' => 'Base Unit Retail',
//            'supplier_nbr' => 'Supplier Nbr',
//            'searched_item_nbr' => 'Searched Item Nbr',
//            'signing_description' => 'Signing Description',
//            'upc_nbr' => 'UPC Nbr'
//        ];
        return ['Searched Item Nbr',
            'Link Type',
            'Result Item Nbr',
            'SBU',
            'Acctg Dept Nbr',
            'Dept Description',
            'Category Description',
            'UPC Nbr',
            'Fineline Number',
            'Season Year(Apparel Only)',
            'PLU Nbr',
            'Item Status Code',
            'Item File Description',
            'Signing Description',
            'Cost',
            'Base Unit Retail',
            'Advertised Retail',
            'Price ID',
            'Was Price',
            'Supplier',
            'Supplier Nbr',
            'Brand Name',
            'Color/Flavor Ranking'];
    }
    
    function getlinkedItemsDBColumns() {
        return [
            'item_file_description' => 'Item File Description',
            'cost' => 'Cost',
            'base_unit_retail' => 'Base Unit Retail',
            'supplier_nbr' => 'Supplier Nbr',
            'searched_item_nbr' => 'Searched Item Nbr',
            'signing_description' => 'Signing Description',
            'upc_nbr' => 'UPC Nbr'
        ];
    
    }
    /**
     * Import File read validations
     * @param path $file
     * @return Error Message as String 
     */
    function bulkImportReadFileValidations($file) {
        if (!empty($file)) {
            $objFileReadValidations = new BulkImportFileReadValidations();
            $objFileReadValidations->setFiles($file);
            return $objFileReadValidations->validate();
        }
    }
    
    /**
     * 
     * @param type $row
     * @return type
     */
    function insertIntoMasterData($row) {

        $objMasterItems = new MasterItems();
        $masterId = 0;

        \DB::beginTransaction();
        try {

            if (isset($row['searched_item_nbr']) && !empty($row['searched_item_nbr']) && isset($row['upc_nbr']) && !empty($row['upc_nbr'])) {

                $searchedItemsNumber = \DB::connection()->getPdo()->quote($row['searched_item_nbr']);
                $dbResult = $objMasterItems->whereRaw('searched_item_nbr =' . $searchedItemsNumber . '')
                                           ->whereRaw('LPAD(REPLACE(upc_nbr,\' \',\'\'), 13, 0) = "' . str_replace(' ', '', $row['upc_nbr']) . '"')
                                           ->orderBy('id', 'desc')
                                           ->limit(1)
                                           ->get(['id']);

                $row['is_primary'] = 2;
                $row['versions'] = '';
                if (count($dbResult) == 0) {
                    $master_data = $objMasterItems->saveRecord($row);
                    $masterId = $master_data->id;
                } else {
                    $masterId = $dbResult[0]->id;
                }
            }

            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }

        return $masterId;
    }

    /**
     * Import the Data into DB
     * @param type $command
     * @return type
     */
    function importBulkData($command) {
        DefaultIniSettings::apply();
        $saveIds = $linkedItemsResponse = $ignoreItems = [];
        $status = false;
        DB::beginTransaction();
        $message = $deletedItems = $totalDeletedItems = [];
        $i = 0;
        $totalCount = 0;
        try {

            $objItemsDs = new ItemsDataSource();
            $objPrcZoneDs = new PriceZonesDataSource();
            $params = $command->dataToArray();
            $users_id = isset($params['users_id']) ? $params['users_id'] : $params['last_modified_by'];
            $file = isset($params['filename']) ? storage_path('app/public') . '/Uploads/bulk_items_import/' . $params['filename'] : '';
            $fileData = $this->getData($file, '|', $this->sheetNo = 0);
            $resultItems = $this->formatItemsArray($fileData[$this->sheetNo], $type = 0);
            unset($fileData);

            if (!empty($resultItems)) {
                $totalCount = count($resultItems);
                /**
                 * Get the HistoricalReferenceDate
                 */
                $objEventsDs = new EventsDataSource();
                $historicalDates = $objEventsDs->getHistoricalReferenceDate($params['events_id']);                
                $aprimoDetails = $objEventsDs->getAprimoDetails($params['events_id']);
                foreach ($resultItems as $data) {
                    
                    if (!empty($data)) {
                        
                        if ($i == 0) {

                            $arrEventData = ['users_id' => $users_id, 'events_id' => PiLib::piEncrypt($params['events_id']),
                                'status' => false,
                                'error' => false,
                                'progress_status' => [
                                    'items' => ['count' => $i, 'progress' => 0, 'total' => $totalCount],
                                    'linkeditems' => ['count' => 0, 'progress' => 0, 'total' => 0]
                                ]
                            ];
                            event(new BulkItemsImportProcess($arrEventData));                            
                        }

                        /**
                         * Ignore ZDELETE & Pricr ID SPECIAL VALUE data while import
                         */
                        if (!in_array('zdelete', $data)) {

                           // if (strtoupper($data['price_id']) != 'SPECIAL VALUE') {
                                
                                /**
                                 * Send data to apply price id rules
                                 */
                                $row = $objItemsDs->applyPriceIdRules($data);                                
                                /*
                                 * Get version code based on traitnumber from import files
                                 */
                                $traitNbr = $this->prepareVersionsbyTraitNbr($row['versions']);                                
                                $versions = $this->getVersionsByTraitNbr($traitNbr);
                                $omitVersions = $this->getOmittedVersionFromImport($row['versions']);                                
                                $omit_string = !empty($omitVersions) ? 'OMIT:' . implode(', ', $omitVersions) : '';
                                $row = $this->changeNumbercolumns($row);

                                //print_r(array_merge($versions, $omitVersions));
                                /**
                                 * If same item, upc and versions exists remove exists and import new items
                                 */
                                if(!empty($versions)){
                                    $searchItemNbr = preg_replace('/[\$,~]/', '', $row['searched_item_nbr']);
                                    $upcNbr = !empty($row['upc_nbr']) ? str_pad(trim($row['upc_nbr']), 13, '0', STR_PAD_LEFT) : '';
                                    //$deletedItems[] = $this->deleteExistItemNbrBySameUpcVersions($searchItemNbr, $upcNbr, array_merge($versions, $omitVersions), $params['events_id']);                                                                        
                                }
                                /**
                                 * format the string values
                                 */
                                $data = $objItemsDs->filterString($row);  
                                $data['advertised_item_description'] = preg_replace( "/\r|\n/", "", $data['advertised_item_description']);
                                $data['dept_description'] = preg_replace( "/\r|\n/", "", $data['dept_description']);
                                $data['category_description'] = preg_replace( "/\r|\n/", "", $data['category_description']);
                                $data['item_file_description'] = preg_replace( "/\r|\n/", "", $data['item_file_description']);
                                $data['signing_description'] = preg_replace( "/\r|\n/", "", $data['signing_description']);
                                $data['short_version_description'] = preg_replace( "/\r|\n/", "", $data['short_version_description']);                
                                $data['upc_nbr'] = !empty($data['upc_nbr']) ? str_pad(trim($data['upc_nbr']), 13, '0', STR_PAD_LEFT) : '';
                                $data['tracking_id'] = $this->unique_id.'-0';
                                $data['items_import_source'] = '1';//Add tracking id for activity log    
                                $data['advertised_retail'] = !empty($data['advertised_retail']) ? preg_replace('/[\$,~]/', '', $data['advertised_retail']) : $data['advertised_retail'];
                                $data['master_items_id'] = $this->insertIntoMasterData($data);                                
                                $objItems = new Items();                                
                                $items = $objItems->saveRecord(array_merge($params, $data));                                
                                $data['items_id'] = $items->id;                                     
                                $getIdByVersions = $objPrcZoneDs->getPriceZoneIdByVersions($versions);
                                $data['price_zones'] = !empty($getIdByVersions)?$getIdByVersions:[];
                                $data['events_id'] = $params['events_id'];                                                        
                                //$isDuplicate = $objPrcZoneDs->checkPageAdBlock(['item_id' => $data['items_id'], 'events_id' => $params['events_id'], 'value' => !empty($getIdByVersions)?$getIdByVersions:[]]);
                                //if (isset($isDuplicate['isNotExists']) && !empty($isDuplicate['isNotExists'])) {
                                $versionsCode = $objPrcZoneDs->saveManualVersions(array('item_id' => $items->id, 'events_id' => $params['events_id'], 'versions' => $versions, 'type' => 2, 'omited_versions' => $omitVersions, 'source' => 'import'));                                    
                                //}
                                $data['versions'] = isset($versionsCode['versions']) && !empty($versionsCode['versions']) ? implode(", ", $versionsCode['versions']) : 'No Price Zone found.';
                                $data['mixed_column2'] = isset($versionsCode['omitVersions']) && !empty($versionsCode['omitVersions']) ? implode(", ", $versionsCode['omitVersions']) : '';
                                $data['event_dates'] = $historicalDates;
                                $page = $this->setStringPad($data['page'], 2);
                                $data['page'] = !empty($page) ? $page : $data['page'];
                                $data['aprimo_campaign_id'] = isset($aprimoDetails['aprimo_campaign_id']) ? $aprimoDetails['aprimo_campaign_id'] : '';
                                $data['aprimo_campaign_name'] = isset($aprimoDetails['aprimo_campaign_name']) ? $aprimoDetails['aprimo_campaign_name'] : '';
                                $data['aprimo_project_id'] = isset($aprimoDetails['aprimo_project_id']) ? $aprimoDetails['aprimo_project_id'] : '';
                                $data['aprimo_project_name'] = isset($aprimoDetails['aprimo_project_name']) ? $aprimoDetails['aprimo_project_name'] : '';
                                $objEdit = new ItemsEditable();
                                $objEdit->saveRecord(array_merge($params, $data));
                                
                                $objNonEdit = new ItemsNonEditable();
                                $objNonEdit->saveRecord(array_merge($params, $data));                                                                                             
                                $saveIds[] = $items->id;
//                            } else {
//                                $ignoreItems[] = [$data['searched_item_nbr']];
//                            }
                        } else {
                            $ignoreItems[] = [$data['searched_item_nbr']];
                        }

                        $i++;
                        $progress = ($i * 100 / $totalCount);
                        if ($i == $totalCount) {
                            $message = ['TotalItems' => $totalCount, 'InsertItemCount' => count($saveIds), 'IgnoredItems' => count($ignoreItems)];
                        }

                        $arrEventData = ['users_id' => $users_id, 'events_id' => PiLib::piEncrypt($params['events_id']),
                            'status' => false,
                            'error' => false,
                            'progress_status' => [
                                'items' => ['count' => $i, 'progress' => $progress, 'total' => $totalCount],
                                'linkeditems' => ['count' => 0, 'progress' => 0, 'total' => 0]
                            ]
                        ];

                        $is_completed = ($totalCount==$i)?true:false;
                        if($is_completed == true){
                            $totalDeletedItems = array_filter($deletedItems);                            
                        }
                        
                        $arrItemInfo = $this->sendDataToBroadCast(array($items->id), $params['events_id'],$is_completed, $totalDeletedItems);                       
                        broadcast(new ItemActions($arrItemInfo, 'import'))->toOthers();
                        event(new BulkItemsImportProcess($arrEventData));
                        
                        
                    }
                }
                unset($deletedItems, $totalDeletedItems);
                $linkedItemsResponse = $this->importLinkedItemsData($params, $message, $totalCount, $users_id);                
                if (file_exists($file)) {
                  unlink($file);
                }
            }
            if(!empty($saveIds)){
                $objLogs = new ActivityLog();
                $logData = $objLogs->setActivityLog(array_merge($params, array('events_id' => $params['events_id'], 'actions' => 'insert', 'users_id' => $users_id, 'count' => count($saveIds), 'type' => '0', 'tracking_id' => $this->unique_id)));                                
                $objLogs->updateActivityLog($logData);
                unset($logData);
            }
            /**
             * Update Events status
             */
            $objItemsDs->updateEventStatus($params['events_id']);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            if (file_exists($file)) {
                unlink($file);
            }
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            $arrEventData = ['users_id' => $users_id, 'events_id' => PiLib::piEncrypt($params['events_id']), 'status' => true, 'error' => true, 'message' => $exMsg];
            event(new BulkItemsImportProcess($arrEventData));
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }

        return ['items_id' => $saveIds];
    }

    /**
     * 
     * @param type $params
     * @return type
     */
    function importLinkedItemsData($params, $message, $totalItemsCount, $users_id) {
        
        DefaultIniSettings::apply();
        DB::beginTransaction();
        $linkMessage = [];
        try {
            $file = isset($params['filename']) ? storage_path('app/public') . '/Uploads/bulk_items_import/' . $params['filename'] : '';
            $fileData = $this->getData($file, '|', $this->sheetNo = 1);
            $linkedItems = $this->formatItemsArray($fileData[$this->sheetNo], $type = 1);
            $linkedItemsColumns = $this->getlinkedItemsDBColumns();
            $arrResponse = $arrIgnoredItems = $saveIds = [];
            
            /**
             * Data Preparations for linked items Data
             */
           
            $alreadyAdded = [];
            foreach ($linkedItems as $data) {
                
                $data['parent_item_nbr'] = $data['searched_item_nbr'];
                unset($data['searched_item_nbr']);

                $data['searched_item_nbr'] = $data['Result Item Nbr'];
                unset($data['Result Item Nbr']);
                
                $parentItemNbr = preg_replace('/[\$,~]/', '', $data['parent_item_nbr']);
                $searchItemNbr = preg_replace('/[\$,~]/', '', $data['searched_item_nbr']);
                $itemUniKey = md5(trim($parentItemNbr).''.trim($searchItemNbr));
                $upcNbr = !empty($data['upc_nbr']) ? str_pad(trim($data['upc_nbr']), 13, '0', STR_PAD_LEFT) : '';
                
                if (trim($parentItemNbr) != trim($searchItemNbr) && !in_array($itemUniKey,$alreadyAdded)) {  
                    $alreadyAdded[$itemUniKey] = $itemUniKey;
                    if (!empty($searchItemNbr) && !empty($upcNbr)) {  
                        
                        $isExists = $this->checkLinkedItemExists($searchItemNbr, $upcNbr, $params['events_id']);
                        
                        if (empty($isExists)) {
                            
                            foreach ($linkedItemsColumns as $key => $columnLable) {
                                if (isset($data[$key])) {                                    
                                    $arrResponse[$searchItemNbr][$key] = $data[$key];                                    
                                    $arrResponse[$searchItemNbr]['items_type'] = '1';                                                                       
                                }
                           }
                        } else {
                            $arrIgnoredItems[] = [$data['searched_item_nbr'], $data['upc_nbr']];
                    
                        }
                    }else{
                        $arrIgnoredItems[] = [$data['searched_item_nbr'], $data['upc_nbr']];
                    
                    }
                } else {
                    $arrIgnoredItems[] = [$data['searched_item_nbr'], $data['upc_nbr']];
                    
                }
          }
            
            /**
             * Insert Linked Items
             */
            $total = count($linkedItems);
            if (!empty($arrResponse)) {
                $status = false;
                $progress = 0;
                $i = 0;                                
                $totalLink = count($arrResponse);
                foreach ($arrResponse as $row) {
                    
                    if($i == 0){
                         $arrEventData = ['users_id' => $users_id, 'events_id' => PiLib::piEncrypt($params['events_id']),
                                          'status' => $status, 
                                          'error' => false,
                                          'progress_status' =>[
                                                         'items'=> ['count' => $totalItemsCount, 'progress' => 100, 'total' => $totalItemsCount], 
                                                         'linkeditems'=> ['count' => $i, 'progress' => $progress, 'total' => $totalLink]
                                                         ]                                     
                                          ];  
                    }
                    $objItems = new Items();
                    $row['items_import_source'] = '1';       
                    $row['tracking_id'] = $this->unique_id.'-1'; //Add tracking id for activity log                                        
                    $row['signing_description'] = preg_replace( "/\r|\n/", "", $row['signing_description']); 
                    $row['upc_nbr'] = !empty($row['upc_nbr']) ? str_pad(trim($row['upc_nbr']), 13, '0', STR_PAD_LEFT) : '';
                    $items = $objItems->saveRecord(array_merge($params, $row));
                    $row['items_id'] = $items->id;
                    $objItemsEdit = new ItemsEditable();
                    $objItemsEdit->saveRecord(array_merge($params, $row));
                    $objItemsNonEdit = new ItemsNonEditable();
                    $objItemsNonEdit->saveRecord(array_merge($params, $row));
                    $saveIds[] = $items->id;
                    
                    $i++;
                    $progress = ($i * 100 / $totalLink);
                    if($i == $totalLink){
                        $status = true;
                        $linkMessage = ['TotalLinkItems' => $total, 'InsertLinkItemCount' => count($saveIds), 'IgnoredLinkItems' => count($arrIgnoredItems)];
                    }
                                        
                    $arrEventData = ['users_id' => $users_id, 'events_id' => PiLib::piEncrypt($params['events_id']),
                                     'status' => $status, 
                                     'error' => false,
                                     'progress_status' =>[
                                                         'items'=> ['count' => $totalItemsCount, 'progress' => 100, 'total' => $totalItemsCount], 
                                                         'linkeditems'=> ['count' => $i, 'progress' => $progress, 'total' => $totalLink]
                                                         ],
                                     'message' => array_merge($message, $linkMessage)                     
                                    ];            
                    $is_completed = ($totalLink == $i) ? true:false;                        
                    $arrItemInfo = $this->sendLinkedItemsDataToBroadCast($items->id, $params['events_id'],$is_completed);                       
                    broadcast(new ItemActions($arrItemInfo, 'importLinkeditems'))->toOthers();
                    event(new BulkItemsImportProcess($arrEventData));
                }
                unset($arrItemInfo);
            }else{
                $linkMessage = ['TotalLinkItems' => $total, 'InsertLinkItemCount' => count($saveIds), 'IgnoredLinkItems' => count($arrIgnoredItems)];                
                $arrEventData = ['users_id' => $users_id, 'events_id' => PiLib::piEncrypt($params['events_id']),
                                 'status' => true, 
                                 'error' => false,
                                 'progress_status' =>[
                                                     'items'=>['count' => $totalItemsCount, 'progress' => 100, 'total' => $totalItemsCount], 
                                                     'linkeditems'=> ['count' => 0, 'progress' => 100, 'total' => 0]
                                                     ],
                                 'message' => array_merge($message, $linkMessage)
                                    ];
                                
                event(new BulkItemsImportProcess($arrEventData));
            }
            if(!empty($saveIds)){
                $objLogs = new ActivityLog();                
                $logData = $objLogs->setActivityLog(array_merge($params, array('events_id' => $params['events_id'], 'actions' => 'insert', 'users_id' => $users_id, 'count' => count($saveIds), 'type' => '1', 'tracking_id' => $this->unique_id)));                
                $objLogs->updateActivityLog($logData);
                unset($logData);
            }
            
            unset($arrResponse);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            $arrEventData = ['users_id' => $users_id, 'events_id' => PiLib::piEncrypt($params['events_id']), 'status' => true, 'error' => true, 'message' => $exMsg];
            event(new BulkItemsImportProcess($arrEventData));
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $saveIds;
    }

    /**
     * 
     * @param type $itemNbr
     * @param type $upcNbr
     * @param type $intEventId
     * @return type
     */  
    function checkLinkedItemExists($itemNbr, $upcNbr, $intEventId){        
        $objItems = new Items();        
        $count = $objItems->where('searched_item_nbr', trim($itemNbr))
                          ->where('upc_nbr', trim($upcNbr))                          
                          ->where('events_id', $intEventId)
                          ->count();                     
        return $count;
        
    }
    /**
     * 
     * @param type $filename
     * @param type $show_empty_fields
     * @param type $sheetNo
     * @return type
     * @throws \Exception
     */
    function getData($filename, $show_empty_fields, $sheetNo) {
        DefaultIniSettings::apply();
        if (file_exists($filename)) {
            set_time_limit(0);
            $data = array();
            $inputFileType = \PHPExcel_IOFactory::identify($filename);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setReadDataOnly(false);
            $reader = $objReader->load($filename);
            $count = $reader->getSheetCount();
            
            
                $fileHeaders = [];
                $excelData = [];

                $highestColumnIndex = 0;
                $sheetNames = $reader->getSheetNames();

                $objWorksheet = $reader->setActiveSheetIndex($sheetNo);
                $highestRow = $objWorksheet->getHighestRow();
                $highestColumn = $objWorksheet->getHighestColumn();
                $highestColumnIndex = ($highestColumnIndex > 0) ? $highestColumnIndex : \PHPExcel_Cell::columnIndexFromString($highestColumn); // here 5				
                for ($row = 0; $row <= $highestRow; ++$row) {
                    for ($col = 0; $col < $highestColumnIndex; ++$col) {
                        $value = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                        if (is_array($data) && !empty($value)) {
                            if (isset($show_empty_fields)) {
                                $data [$sheetNo] [$row] [$col] = $value;
                            } else {
                                $data [$sheetNo] [$row] [] = $value;
                            }
                        }
                    }
                }
                $highestColumnIndex = 0;
            
            return $data;
        } else {
            throw new \Exception('File not Found');
        }
    }
    /**
     * 
     * @param type $filename
     * @return type
     * @throws \Exception
     */
    function getSheetName($filename) {
        DefaultIniSettings::apply();
        //$count = 0;
        $sheetNames = [];
        if (file_exists($filename)) {
            set_time_limit(0);
            $data = array();
            $inputFileType = \PHPExcel_IOFactory::identify($filename);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setReadDataOnly(false);
            $reader = $objReader->load($filename);
            //$count = $reader->getSheetCount();
            $sheetNames = $reader->getSheetNames();
            return $sheetNames;
            //return $count;
        } else {
            throw new \Exception('File not Found');
        }
    }
    
    function defaultSheetNames(){
        return ['Result Items', 'Linked Items'];
    }

    /**
     * 
     * @param type $records
     * @param type $type
     * @return type
     */
    function formatItemsArray($records, $type = 0) {
        $arrHeaders = $records[1];
        $dbHeaders = $this->setDBColumns($arrHeaders);
        $fileDataWithoutHeaders = array_slice($records, 1);
        
        $arrData = [];
        foreach ($dbHeaders as $key => $column) {
            
            $i = 0;
            foreach ($fileDataWithoutHeaders as $data) {
                $arrData[$i][$column] = isset($data[$key]) ? $data[$key] : '';
                if ($column == 'attributes') {
                    $attributes = $this->getMasterOptionValuesByName(isset($data[$key]) ? trim($data[$key]) : '');
                    $arrData[$i]['attributes'] = !empty($attributes) ? \GuzzleHttp\json_encode($attributes) : '';
                }

                if ($type != 1) {
                    unset($arrData[$i]['Result Item Nbr']);
                }
                $i++;
            }
        }
        
        unset($dbHeaders, $arrHeaders);
        return $arrData;
    }

    /**
     * 
     * @return type
     */
    function getItemsColumnKey() {
        $objItemsHeader = new ItemsHeaders();
        $result = $objItemsHeader->where('status', '1')->get()->toArray();
        $arrHeaders = [];
        foreach ($result as $row) {
            $arrHeaders[$row['column_label']] = $row['column_name'];
        }
        return $arrHeaders;
    }
    /**
     * 
     * @param type $excelHeaders
     * @return string
     */
    function setDBColumns($excelHeaders) {
        $defaultHeaders = $this->getItemsColumnKey();
        $dbColumn = [];
        foreach ($excelHeaders as $headers) {
            if ($headers == 'Searched Item Nbr') {
                $headers = 'Item Nbr';
            }
            if ($headers == 'New, Exclusive, and/or Made in America?') {
                $headers = 'Attributes';
            }
            if ($headers == 'Versions') {
                $headers = 'Price Versions';
            }
            if (isset($defaultHeaders[$headers]) && !empty($defaultHeaders[$headers])) {
                $dbColumn[] = $defaultHeaders[$headers];
            } else {
                $dbColumn[] = $headers;
            }
        }
        return $dbColumn;
    }
    /**
     * 
     * @param type $itemsId
     * @param type $intEventsID
     * @return type
     */
    function sendDataToBroadCast($itemsId, $intEventsID,$is_completed=false, $deletedItems = array()) {
        
        $objCopyDs = new CopyDs();
        $dataParent['items_id'] = $itemsId;
        $dataParent['event_id'] = $intEventsID;                
        $returnResult['objResult'] = $objCopyDs->getItemListById($dataParent);
        
        $objGridResponse = new ItemsGridDataResponse();
        $response = $objGridResponse->getGridResponse($returnResult, array(), $intEventsID);
        $response['event_id'] = PiLib::piEncrypt($intEventsID);
        $response['is_completed'] = $is_completed;
        $response['deleted_items'] = $deletedItems;
        $response['status'] = true;
        return $response;
    }
    
    /**
     * Send Linked itemdata to braodcasting  
     * @param type $itemsId
     * @param type $intEventsID
     * @param type $is_completed
     * @return array
     */
    function sendLinkedItemsDataToBroadCast($itemsId, $intEventsID, $is_completed = false) {
        
        /**
         * Get data by id
         */
        $objLinkDs = new LinkedItemsDataSource();
        $params = ['link_item_id' => $itemsId, 'event_id' => $intEventsID];
        $result = $objLinkDs->getLinkedItemsByPrimId($params);
        unset($params);
        /**
         * Format the query result
         */
        $objGridResponse = new LinkedItemsGridDataResponse();
        $response = $objGridResponse->getGridResponse($result);        
        $response['event_id'] = PiLib::piEncrypt($intEventsID);
        $response['is_completed'] = $is_completed;
        $response['deleted_items'] = array();        
        $response['status'] = true;
        unset($result);
        
        return $response;
    }

    /**
     * Get Attributes Id from Master table based on Atrribute name Ex : New, Exclusive
     * @param strin $string
     * @return Array
     */
    function getMasterOptionValuesByName($string) {
        $arrValues = explode(', ', trim(strtolower(\DB::connection()->getPdo()->quote($string))));

        /**
         * Remove if attribute is usda organic
         */
        $removeIndex = array_search('usda organic', $arrValues);
        if (isset($arrValues[$removeIndex]) && $arrValues[$removeIndex] == 'usda organic') {
            unset($arrValues[$removeIndex]);
        }

        $objMasterOption = new MasterDataOptions();
        $result = $objMasterOption->whereRaw('lower(name) in(' . trim(strtolower(implode("','", $arrValues))) . ')')
                                  ->get()
                                  ->toArray();
        $data = NULL;
        if (!empty($result)) {
            foreach ($result as $row) {
                $data[] = $row['id'];
            }
        }
        return $data;
    }

    /**
     *  
     * @param String $upcNbr
     * @param String $itemNbr
     * @param Int $intEventID
     * @return Array
     */
    function getItemsIdByUpcItemNbr($upcNbr, $itemNbr, $intEventID) {

        $objItems = new Items();
        $result = $objItems->where('searched_item_nbr', $itemNbr)
                           ->where('upc_nbr', $upcNbr)
                           ->where('events_id', $intEventID)
                           ->where('items_type', '0')
                           ->get()
                           ->toArray();
        $data = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $data[] = $row['id'];
            }
        }
        return $data;
    }

    /**
     * Split the Trait number from Excel values
     * If any versions contain OMIT: after OMIT versions will be excluded
     * @param String $string
     * @return Array
     */
//    function prepareVersionsbyTraitNbr($string) {
//        $uid = $string;
//        $split = explode('OMIT',$uid);
//        dd($split);
//        $array = $traitNbr = [];
//        if (!empty($string)) {
//            $explodeString = explode(',', $string);
//            
//            foreach ($explodeString as $value) {
//                $explode = explode('OMIT:', $value);
//                $array = $explode[0];
//
//                $explodeCode = explode('_', $array);
//                
//                if(count($explodeCode) >= 3){                   
//                    $traitNbr[] = isset($explodeCode[2]) ? trim($explodeCode[2]) : '';
//                }else{
//                    $traitNbr[] = isset($explodeCode[1]) ? trim($explodeCode[1]) : '';
//                }
//                if (count($explode) > 1) {
//                    break;
//                }
//            }
//            unset($array); 
//            $traitNbr = array_filter($traitNbr); //remove empty values
//        }
//        
//        return $traitNbr;
//    }
    function prepareVersionsbyTraitNbr($string) {
        $traitNbr = [];
        if (!empty($string)) {
            $explodeNonOmit = explode('OMIT', $string);
            if (isset($explodeNonOmit[0]) && !empty($explodeNonOmit[0])) {
                $explodeString = explode(',', $explodeNonOmit[0]);
                foreach ($explodeString as $value) {
                    $explodeCode = explode('_', $value);
                    if (count($explodeCode) >= 3) {
                        $traitNbr[] = isset($explodeCode[2]) ? trim($explodeCode[2]) : '';
                    } else {
                        $traitNbr[] = isset($explodeCode[1]) ? trim($explodeCode[1]) : '';
                    }
                }
            }
            $traitNbr = array_filter($traitNbr); //remove empty values
        }
        
        return $traitNbr;
    }

    /**
     * 
     * @param array $traitNbr
     * @return Array
     */
    function getVersionsByTraitNbr(array $traitNbr) {
        $versions = [];
        if (!empty($traitNbr)) {
            $objpriceZone = new \CodePi\Base\Eloquent\PriceZones();
            $result = $objpriceZone->whereIn('trait_nbr', $traitNbr)->get(['versions'])->toArray();
            if (!empty($result)) {
                foreach ($result as $data) {
                    $versions[] = $data['versions'];
                }
            }
        }
        asort($versions);
        return $versions;
    }
    /**
     *
     * @param array $traitNbr
     * @return type
     */
    function getDbTraitNbr(array $traitNbr) {
        $versions = [];
        if (!empty($traitNbr)) {
            $objpriceZone = new \CodePi\Base\Eloquent\PriceZones();
            $result = $objpriceZone->whereIn('trait_nbr', $traitNbr)->get(['trait_nbr'])->toArray();
            if (!empty($result)) {
                foreach ($result as $data) {
                    $versions[] = $data['trait_nbr'];
                }
            }
        }
        return $versions;
    }
    
    /**
     * Check the same ItemNbr, UPC Nbr and Versions already exists , it will remove from listbuilder
     * @param String $itemNbr
     * @param String $upcNbr
     * @param array $versions
     * @param int $intEventId
     */
    function deleteExistItemNbrBySameUpcVersions($itemNbr, $upcNbr, $versions, $intEventId) {

        DB::beginTransaction();
        $itemsId = [];
        try {
            $objItems = new Items();
            asort($versions);            
            $stringVersions = implode(', ', $versions);            
            //\DB::enableQueryLog();
            $result = $objItems->dbTable('i')
                               ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                               ->select('i.id')
                               ->where('searched_item_nbr', $itemNbr)
                               ->where('upc_nbr', $upcNbr)
                               ->where('events_id', $intEventId)
                               ->whereRaw('trim(versions) = "' . trim($stringVersions) . '"')
                               ->get();
            //$query = \DB::getQueryLog();
            //print_r($query);
            if (count($result) > 0) {
                foreach ($result as $row) {
                    $itemsId[] = $row->id;
                }
                //print_r($itemsId);
                if (!empty($itemsId)) {
                    $objItems->whereIn('id', $itemsId)->delete();
                    $objItemsEdit = new ItemsEditable();
                    $objItemsEdit->whereIn('items_id', $itemsId)->delete();
                    $objItemsNonEdit = new ItemsNonEditable();
                    $objItemsNonEdit->whereIn('items_id', $itemsId)->delete();
                    $objItemsPriceZones = new \CodePi\Base\Eloquent\ItemsPriceZones();
                    $objItemsPriceZones->whereIn('items_id', $itemsId)->delete();
                }
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return array_shift($itemsId);
    }

    /****************************Manual Import START ***********************/    
    /**
     * This is temporary function to import excel data into DB
     * This is throug command it will run
     * Command : php artisan manualImport
     */
    function importManualExcel() {
        DefaultIniSettings::apply();
        \DB::beginTransaction();
        echo'Start';
        echo date('ymd H:i:s');
        try {

            //$file = storage_path('app/public') . '/Uploads/manual_import_items/20180525_06_TAB_Master_UPDATE.xlsx';            
            $file = storage_path('app/public') . '/Uploads/113018_TAB_Master_Pencil_100318.xlsx';
            $fileData = $this->getData($file, '|', $this->sheetNo = 0);

            $arrHeaders = $fileData[0][1];
            $dbHeaders = $this->setDBColumns($arrHeaders);

            $fileDataWithoutHeaders = array_slice($fileData[0], 1);
            $arrData = [];
            foreach ($dbHeaders as $key => $column) {
                $i = 0;
                foreach ($fileDataWithoutHeaders as $values) {
                    if (!in_array('zdelete', $values)) {
                        $arrData[$i][$column] = isset($values[$key]) ? $values[$key] : '';
                        if ($column == 'attributes') {
                            $attributes = $this->getMasterOptionValuesByName(isset($values[$key]) ? trim($values[$key]) : '');
                            $arrData[$i]['attributes'] = !empty($attributes) ? \GuzzleHttp\json_encode($attributes) : '';
                        }
                    }
                    $i++;
                }
            }

            $objItemsDs = new ItemsDataSource();
            $objPrcZoneDs = new PriceZonesDataSource();
            $params['events_id'] = 234;
            $params['created_by'] = 1;
            $params['date_added'] = PiLib::piDate();
            $params['last_modified'] = PiLib::piDate();
            $params['gt_date_added'] = gmdate("Y-m-d H:i:s");
            $params['gt_last_modified'] = gmdate("Y-m-d H:i:s");

            /**
             * Get the HistoricalReferenceDate
             */
            $objEventsDs = new EventsDataSource();
            $historicalDates = $objEventsDs->getHistoricalReferenceDate($params['events_id']);
            foreach ($arrData as $data) {

                if (!empty($data)) {

                    /**
                     * Send data to apply price id rules
                     */
                    $row = $objItemsDs->applyPriceIdRules($data);
                    /*
                     * Get version code based on traitnumber from import files
                     */
                    $traitNbr = $this->prepareVersionsbyTraitNbr($row['versions']);
                    $versions = $this->getVersionsByTraitNbr($traitNbr);
                    $omitVersions = $this->getOmittedVersionFromImport($row['versions']);
                    $row = $this->changeNumbercolumns($row);

                    /**
                     * format the string values
                     */
                    $data = $objItemsDs->filterString($row);

                    $data['advertised_item_description'] = preg_replace("/\r|\n/", "", $data['advertised_item_description']);
                    $data['short_version_description'] = preg_replace("/\r|\n/", "", $data['short_version_description']);
                    $data['upc_nbr'] = !empty($data['upc_nbr']) ? str_pad(trim($data['upc_nbr']), 13, '0', STR_PAD_LEFT) : '';
                    $data['tracking_id'] = $this->unique_id . '-0';
                    $data['items_import_source'] = '1';
                    unset($data['Result Item Nbr']);

                    $objItems = new Items();
                    $items = $objItems->saveRecord(array_merge($params, $data));
                    $data['items_id'] = $items->id;
                    $versionsCode = $objPrcZoneDs->saveManualVersions(array('item_id' => $items->id, 'events_id' => $params['events_id'], 'versions' => $versions, 'type' => 2, 'omited_versions' => $omitVersions, 'source' => 'import'));
                    $data['versions'] = isset($versionsCode['versions']) && !empty($versionsCode['versions']) ? implode(", ", $versionsCode['versions']) : "No Price Zone found.";
                    $data['event_dates'] = $historicalDates;
                    $objEdit = new ItemsEditable();
                    $objEdit->saveRecord(array_merge($params, $data));
                    $objNonEdit = new ItemsNonEditable();
                    $objNonEdit->saveRecord(array_merge($params, $data));
                }
            }
            \DB::commit();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . $ex->getLine() . $ex->getFile();
            exit;
            \DB::rollback();
        }
        echo'End';
        echo date('ymd H:i:s');
    }
    
    /**
     * This is temporary function to import linked items excel data into DB
     * This is throug command it will run
     * Command : php artisan manualImport
     */
    function importLinkedItemsManual() {

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 5000);
        DB::beginTransaction();
        try {
            $file = storage_path('app/public') . '/Uploads/Bulk_import_update3.xlsx';
            $fileData = $this->getData($file, '|', $this->sheetNo = 5);
            $linkedItems = $this->formatItemsArray($fileData[$this->sheetNo], $type = 1);
            unset($fileData);
            $linkedItemsColumns = $this->getlinkedItemsDBColumns();
            //$arrResponse = [];
//            foreach ($linkedItems as $data) {
//
//                $data['parent_item_nbr'] = $data['searched_item_nbr'];
//                unset($data['searched_item_nbr']);
//                $data['searched_item_nbr'] = $data['Result Item Nbr'];
//                unset($data['Result Item Nbr']);
//                $parentItemNbr = preg_replace('/[\$,~]/', '', $data['parent_item_nbr']);
//                $searchItemNbr = preg_replace('/[\$,~]/', '', $data['searched_item_nbr']);
//
//                //if (trim($parentItemNbr) != trim($searchItemNbr)) {
//                    if (!empty($searchItemNbr) && !empty($data['upc_nbr'])) {
//                        foreach ($linkedItemsColumns as $key => $columnLable) {
//                            if (isset($data[$key])) {
//                                $arrResponse[$searchItemNbr][$key] = $data[$key];
//                                $arrResponse[$searchItemNbr]['items_type'] = '1';
//                            }
//                        }
//                    }
//                //}
//            }
            //unset($linkedItems, $linkedItemsColumns);
            if (!empty($linkedItems)) {

                $params['events_id'] = 204;
                $params['created_by'] = 1;
                $params['date_added'] = PiLib::piDate();
                $params['last_modified'] = PiLib::piDate();
                $params['gt_date_added'] = gmdate("Y-m-d H:i:s");
                $params['gt_last_modified'] = gmdate("Y-m-d H:i:s");

                foreach ($linkedItems as $row) {
                    unset($row['searched_item_nbr']);
                    $objItems = new Items();
                    $row['items_type'] = '1';
                    $row['searched_item_nbr'] = preg_replace('/[\$,~]/', '', $row['Result Item Nbr']);
                    unset($row['Result Item Nbr']);
                    $row['items_import_source'] = '1';
                    $row['tracking_id'] = $this->unique_id . '-1'; //Add tracking id for activity log                                        
                    $row['item_file_description'] = preg_replace("/\r|\n/", "", $row['item_file_description']);
                    $row['upc_nbr'] = str_pad($row['upc_nbr'], 13, '0', STR_PAD_LEFT);
                    $items = $objItems->saveRecord(array_merge($params, $row));
                    $row['items_id'] = $items->id;
                    $objItemsEdit = new ItemsEditable();
                    $objItemsEdit->saveRecord(array_merge($params, $row));
                    $objItemsNonEdit = new ItemsNonEditable();
                    $objItemsNonEdit->saveRecord(array_merge($params, $row));
                }
            }
            unset($linkedItems);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return true;
    }
 /************************************Manual Import END************************/   
    
    /**
     * Format Number values, remove unwanted characters
     * @param array $data
     * @return string
     */
    function changeNumbercolumns($data){
        $row = [];
        if(!empty($data)){
            $numeriValues = array('searched_item_nbr', 'cost', 'upc_nbr', 'fineline_number', 'plu_nbr',
                                  'was_price', 'supplier_nbr', 'acctg_dept_nbr', 'base_unit_retail',
                                  'forecast_sales', 'save_amount', 'itemsid'
                                  );
            foreach ($data as $key => $value){
                if(in_array($key, $numeriValues)){
                    $formatValues = preg_replace('/[\$,~]/', '', $value);
                    $row[$key] = (is_numeric(trim($formatValues))) ? trim($formatValues) : '';
                }else{
                    $row[$key] = $value;
                }
            }
        }
        return $row;
    }
    /**
     * 
     * @param type $string
     * @return type
     */
    function getOmittedVersionFromImport($string) {
        
        $splitOmit = explode('OMIT:', $string);
        $stringOmit = isset($splitOmit[1]) ? $splitOmit[1] : [];
        $versions = [];
        if (!empty($stringOmit)) {
            $omitVersions = explode(',', $stringOmit);
            foreach ($omitVersions as $code) {
                $explodeCode = explode('_', $code);
                if (count($explodeCode) >= 3) {
                    $traitNbr[] = isset($explodeCode[2]) ? trim($explodeCode[2]) : '';
                } else {
                    $traitNbr[] = isset($explodeCode[1]) ? trim($explodeCode[1]) : '';
                }
            }
            $versions = $this->getVersionsByTraitNbr($traitNbr);
        }

        return $versions;
    }
    /**
     * Import Csv data file
     * @param string $file
     * @param type $events_id
     */
    function importCsvFile($file, $events_id) {
        date_default_timezone_set(timezone_name_from_abbr("UTC"));
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        \DB::beginTransaction();
        try {
            echo 'Start :: ' . date('y-m-d H:i:s');
            $file = storage_path('app/public') . '/Uploads/' . $file;
            $fileData = $this->getData($file, '|', $this->sheetNo = 0);
            $arrHeaders = $fileData[0][1];
            $dbHeaders = $this->setDBColumns($arrHeaders);
            $fileDataWithoutHeaders = array_slice($fileData[0], 1);
            $arrData = [];
            foreach ($dbHeaders as $key => $column) {
                $i = 0;
                foreach ($fileDataWithoutHeaders as $values) {
                    if (!in_array('zdelete', $values)) {
                        $arrData[$i][$column] = isset($values[$key]) ? $values[$key] : '';
                        if ($column == 'attributes') {
                            $attributes = $this->getMasterOptionValuesByName(isset($values[$key]) ? trim($values[$key]) : '');
                            $arrData[$i]['attributes'] = !empty($attributes) ? \GuzzleHttp\json_encode($attributes) : '';
                        }
                    }
                    $i++;
                }
            }

//            $fileData = [];
//            $file_handle = fopen($file, 'r');
//            while (!feof($file_handle)) {
//                $fileData[] = fgetcsv($file_handle, 1024, ',');
//            }
//            fclose($file_handle);            
//            $arrHeaders = $fileData[0];
//            $dbHeaders = $this->setDBColumns($arrHeaders);            
//            $arrData = [];
//            unset($fileData[0]);
//            foreach ($dbHeaders as $key => $column) {
//                $i = 0;
//                if (!empty($fileData)) {
//                    foreach ($fileData as $values) {
//                        if (isset($values[$key]) && strtolower($values[$key]) != 'zdelete') {
//                            $arrData[$i][$column] = isset($values[$key]) ? $values[$key] : '';
//                            if ($column == 'attributes') {
//                                $attributes = $this->getMasterOptionValuesByName(isset($values[$key]) ? trim($values[$key]) : '');
//                                $arrData[$i]['attributes'] = !empty($attributes) ? \GuzzleHttp\json_encode($attributes) : '';
//                            }
//                            $i++;
//                        }
//                    }
//                }
//            }            
//            unset($fileData);
            $final_chunk = array_chunk($arrData, 500);
            unset($arrData);
            if (!empty($final_chunk)) {
                $params['events_id'] = $events_id;
                $params['created_by'] = 1;
                $params['last_modified_by'] = 1;
                $params['date_added'] = PiLib::piDate();
                $params['last_modified'] = PiLib::piDate();
                $params['gt_date_added'] = gmdate("Y-m-d H:i:s");
                $params['gt_last_modified'] = gmdate("Y-m-d H:i:s");
                $params['ip_address'] = \Request::getClientIp();

                $objEventsDs = new EventsDataSource();
                $historicalDates = $objEventsDs->getHistoricalReferenceDate($params['events_id']);
                $aprimoDetails = $objEventsDs->getAprimoDetails($params['events_id']);
                foreach ($final_chunk as $array) {
                    foreach ($array as $data) {
                        if (!empty($data)) {
                            //if (!in_array('zdelete', $data)) {
                            /**
                             * Send data to apply price id rules
                             */
                            $objItemsDs = new ItemsDataSource();
                            $objPrcZoneDs = new PriceZonesDataSource();
                            $row = $objItemsDs->applyPriceIdRules($data);
                            /*
                             * Get version code based on traitnumber from import files
                             */
                            $traitNbr = $this->prepareVersionsbyTraitNbr($row['versions']);
                            $versions = $this->getVersionsByTraitNbr($traitNbr);
                            $omitVersions = $this->getOmittedVersionFromImport($row['versions']);
                            //$omit_string = !empty($omitVersions) ? 'OMIT:' . implode(', ', $omitVersions) : '';

                            $row = $this->changeNumbercolumns($row);

                            /**
                             * format the string values
                             */
                            $data = $objItemsDs->filterString($row);

                            $data['advertised_item_description'] = preg_replace("/\r|\n/", "", $data['advertised_item_description']);
                            $data['short_version_description'] = preg_replace("/\r|\n/", "", $data['short_version_description']);
                            $data['upc_nbr'] = !empty($data['upc_nbr']) ? str_pad(trim($data['upc_nbr']), 13, '0', STR_PAD_LEFT) : '';
                            $data['gtin_nbr'] = !empty($data['gtin_nbr']) ? str_pad(trim($data['gtin_nbr']), 14, '0', STR_PAD_LEFT) : '';
                            $data['tracking_id'] = $this->unique_id . '-0';
                            $data['items_import_source'] = '1';
                            $data['advertised_retail'] = !empty($data['advertised_retail']) ? preg_replace('/[\$,~]/', '', $data['advertised_retail']) : $data['advertised_retail'];
                            unset($data['Result Item Nbr']);
                            $data['master_items_id'] = $this->insertIntoMasterData($data);
                            $objItems = new Items();
                            $items = $objItems->saveRecord(array_merge($params, $data));
                            $data['items_id'] = $items->id;
                            $versionsCode = $objPrcZoneDs->saveManualVersions(array('item_id' => $items->id, 'events_id' => $params['events_id'], 'versions' => $versions, 'type' => 2, 'omited_versions' => $omitVersions, 'source' => 'import'));
                            $data['versions'] = isset($versionsCode['versions']) && !empty($versionsCode['versions']) ? implode(", ", $versionsCode['versions']) : 'No Price Zone found.';
                            $data['mixed_column2'] = isset($versionsCode['omitVersions']) && !empty($versionsCode['omitVersions']) ? implode(", ", $versionsCode['omitVersions']) : '';
                            $data['event_dates'] = $historicalDates;
                            $page = $this->setStringPad($data['page'], 2);
                            $data['page'] = !empty($page) ? $page : $data['page'];
                            $data['aprimo_campaign_id'] = isset($aprimoDetails['aprimo_campaign_id']) ? $aprimoDetails['aprimo_campaign_id'] : '';
                            $data['aprimo_campaign_name'] = isset($aprimoDetails['aprimo_campaign_name']) ? $aprimoDetails['aprimo_campaign_name'] : '';
                            $data['aprimo_project_id'] = isset($aprimoDetails['aprimo_project_id']) ? $aprimoDetails['aprimo_project_id'] : '';
                            $data['aprimo_project_name'] = isset($aprimoDetails['aprimo_project_name']) ? $aprimoDetails['aprimo_project_name'] : '';
                            $objEdit = new ItemsEditable();
                            $objEdit->saveRecord(array_merge($params, $data));
                            $objNonEdit = new ItemsNonEditable();
                            $objNonEdit->saveRecord(array_merge($params, $data));
                            //}
                        }
                    }
                }
            }
            unset($final_chunk);
            $objItemsDs = new ItemsDataSource();
            $objItemsDs->updateEventStatus($params['events_id']);
            \DB::commit();
            echo 'End :: ' . date('y-m-d H:i:s');
        } catch (\Exception $ex) {
            \DB::rollback();
            echo $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            exit;
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
    }

    /**
     * Import Csv file -> linked items
     * @param string $file
     * @param type $events_id
     */
    function importCsvLinkedItems($file, $events_id) {
        \DB::beginTransaction();
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        try {
            echo 'Start :: ' . date('y-m-d H:i:s');
            $file = storage_path('app/public') . '/Uploads/' . $file;
            $fileData = [];
            $file_handle = fopen($file, 'r');
            while (!feof($file_handle)) {
                $fileData[] = fgetcsv($file_handle, 1024);
            }
            fclose($file_handle);
            $arrHeaders = $arrHeaders = $fileData[0];
            $dbHeaders = $this->setDBColumns($arrHeaders);
            unset($fileData[0]);
            $fileDataWithoutHeaders = $fileData;
            $arrData = [];
            foreach ($dbHeaders as $key => $column) {
                $i = 0;
                foreach ($fileDataWithoutHeaders as $value) {
                    $arrData[$i][$column] = isset($value[$key]) ? $value[$key] : '';
                    $i++;
                }
            }
            unset($fileDataWithoutHeaders);
            $final_chunk = array_chunk($arrData, 500);
            unset($arrData);
            $params['events_id'] = $events_id;
            $params['created_by'] = 1;
            $params['last_modified_by'] = 1;
            $params['date_added'] = PiLib::piDate();
            $params['last_modified'] = PiLib::piDate();
            $params['gt_date_added'] = gmdate("Y-m-d H:i:s");
            $params['gt_last_modified'] = gmdate("Y-m-d H:i:s");
            $params['ip_address'] = \Request::getClientIp();



            foreach ($final_chunk as $data) {
                foreach ($data as $row) {
                    unset($row['searched_item_nbr']);
                    $row['searched_item_nbr'] = preg_replace('/[\$,~]/', '', $row['Result Item Nbr']);
                    unset($row['Result Item Nbr']);
                    $searchItemNbr = preg_replace('/[\$,~]/', '', $row['searched_item_nbr']);
                    $upcNbr = !empty($row['upc_nbr']) ? str_pad(trim($row['upc_nbr']), 13, '0', STR_PAD_LEFT) : '';
                    $isExists = $this->checkLinkedItemExists($searchItemNbr, $upcNbr, $params['events_id']);
                    if (empty($isExists)) {
                        $row['items_type'] = '1';
                        $row['items_import_source'] = '1';
                        $row['tracking_id'] = $this->unique_id . '-1'; //Add tracking id for activity log                                        
                        $row['item_file_description'] = preg_replace("/\r|\n/", "", $row['item_file_description']);
                        $row['signing_description'] = preg_replace("/\r|\n/", "", $row['signing_description']);
                        $row['upc_nbr'] = str_pad($row['upc_nbr'], 13, '0', STR_PAD_LEFT);
                        $objItems = new Items();
                        $items = $objItems->saveRecord(array_merge($params, $row));
                        $row['items_id'] = $items->id;
                        $objItemsEdit = new ItemsEditable();
                        $objItemsEdit->saveRecord(array_merge($params, $row));
                        $objItemsNonEdit = new ItemsNonEditable();
                        $objItemsNonEdit->saveRecord(array_merge($params, $row));
                    }
                }
            }
            unset($final_chunk);
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
    }

    /**
     * Migration script to update master items id to exists import items
     */
    function updateMasterItemsID() {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $exMsg = '';
        \DB::beginTransaction();
        try {
            $objItems = new Items();
            $sql = "SELECT 
                    i.id AS item_id, landing_comment,size,buyer_user_id,signing_description,acctg_dept_nbr,sbu,dept_description,category_description,items_status_code,
                    itemsid,offers_id,season_year,landing_url,item_image_url,dotcom_thumbnail,item_file_description,dotcom_description,marketing_description,
                    cost,base_unit_retail,dotcom_price,supplier_nbr,brand_name,gtin_nbr,fineline_number,searched_item_nbr,upc_nbr,made_in_america
                    FROM items AS i
                    INNER JOIN items_editable AS ie ON ie.items_id = i.id
                    INNER JOIN items_non_editable AS ine ON ine.items_id = i.id
                    WHERE i.master_items_id=0 AND i.items_type='0' 
                    AND i.searched_item_nbr!='' AND i.upc_nbr!='' AND i.is_no_record='0'                    
                    AND(i.searched_item_nbr !='N/A' AND i.searched_item_nbr = '~')                   
                    ORDER BY i.id  ";
            $result = $objItems->dbSelect($sql);
            $objitemDs = new ItemsDataSource();
            $array = $objitemDs->doArray($result);
            $array_chunk = array_chunk($array, 1000);
            if(!empty($array_chunk)){
                foreach ($array_chunk as $row) {
                    foreach ($row as $data) {
                        $data['item_file_description'] = preg_replace("/\r|\n/", "", $data['item_file_description']);
                        $data['dotcom_description'] = preg_replace("/\r|\n/", "", $data['dotcom_description']);
                        $data['marketing_description'] = preg_replace("/\r|\n/", "", $data['marketing_description']);
                        $data['upc_nbr'] = str_pad($data['upc_nbr'], 13, '0', STR_PAD_LEFT);
                        $data['is_primary'] = 2;
                        $master_id = $this->insertIntoMasterData($data);
                        $objItems->where('id', $data['item_id'])->update(['master_items_id' => $master_id]);
                    }
                }
            }
            \DB::commit();
            $exMsg = count($array);
        } catch (\Exception $ex) {
            \DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $exMsg;
    }
    /**
     * 
     * @param type $value
     * @param type $position
     */
    function setStringPad($value, $position = 2) {
        $string = '';
        $findNumber = preg_replace("/[^0-9]{1,4}/", '', $value);
        /**
         * Check input having string along with number or only number, if only number allow to add page value
         */
        if (strlen($value) == strlen($findNumber)) {

            if ($findNumber != 0) {
                $string = str_pad($findNumber, $position, '0', STR_PAD_LEFT);
            }
        }
        
        return $string;
    }

}