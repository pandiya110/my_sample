<?php

namespace CodePi\Items\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Items;
use CodePi\Base\Eloquent\Events;
use CodePi\Base\Eloquent\Users;
use GuzzleHttp;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Libraries\PiLib;
use Auth,
    Session;
use App\User;
use CodePi\Api\Commands\GetMasterItems;
use URL,
    DB;
use App\Events\ItemsActivityLogs;
use CodePi\Api\DataSource\EmiApiDataSource;
use CodePi\Items\DataSource\PriceZonesDataSource;
use CodePi\Items\DataSource\CopyItemsDataSource;
use CodePi\Items\Utils\ItemsIQSRequest;

class AppendReplaceItemsDS {

    private $unique_id;

    function __construct() {
        $this->unique_id = mt_rand() . time();
    }

    /**
     * Replace items
     * @param type $command
     * @return array
     */
    function replaceItems($command) {
        $replaceResponse = [];
        $params = $command->dataToArray();
        $deleted_items = [];
        $deleted_items = $this->deleteRecordByItemsId($params);
        $command->items = [$params['item_value']];
        $command->search_key = $params['item_key'];
        $command->event_id = $params['events_id'];
        $command->userEditable = [];
        $command->is_price_req = 1;
        $objItemDS = new ItemsDataSource();
        $cmdResponse = $objItemDS->saveItems($command);
        $replaceResponse['items_id'] = $cmdResponse['items_id'];
        $deleted_items = array_merge($deleted_items, isset($cmdResponse['deleted_items']) ? $cmdResponse['deleted_items'] :[]);
        $replaceResponse['deleted_id'] = $deleted_items;

        return $replaceResponse;
    }

    /**
     * Append items
     * @param type $command
     * @return array
     */
    function appendItems($command) {

        $appendResponse = $deleted_items = [];
        $params = $command->dataToArray();
        $usersData = $this->getUserFieldsData($command);
        $command->items = [$params['item_value']];
        $command->search_key = $params['item_key'];
        $command->event_id = $params['events_id'];
        $command->userEditable = $usersData;
        $command->is_price_req = 1;
        $objItemDS = new ItemsDataSource();
        $cmdResponse = $objItemDS->saveItems($command);
        $appendResponse['items_id'] = isset($cmdResponse['items_id']) ? $cmdResponse['items_id'] : [];
        $deleted_items = $this->deleteRecordByItemsId($params);        
        $deleted_items = array_merge($deleted_items, isset($cmdResponse['deleted_items']) ? $cmdResponse['deleted_items'] :[]);
        $appendResponse['deleted_id'] = $deleted_items;

        return $appendResponse;
    }
    
    function doArray($data) {
        return collect($data)->map(function($x) {
                    return (array) $x;
                })->toArray();
    }
    
    /**
     * Delete the append/replace items row
     * @param array $params
     * @return array
     */
    function deleteRecordByItemsIdOld($params) {
        $row_id = isset($params['id']) ? $params['id'] : 0;
        $event_id = isset($params['events_id']) ? $params['events_id'] : 0;
        $intMasterId = 0;
        $isNoRecord = 0;
        $objItems = new Items();        
        $deletedItems = $countBy = [];
        $objItems->dbTransaction();
        
        try {

            $arrResult = $objItems->where('id', $row_id)->get()->toArray();
            
            foreach ($arrResult as $row){
                $intMasterId = $row['master_items_id'];
                $isNoRecord = $row['is_no_record'];
            }
           
            if (!empty($intMasterId) && $isNoRecord == '0') {
                $deletedItems = $this->getDeletedItemsByItemId($intMasterId, $event_id);
                $countBy = $this->getItemsCountByItemsType($intMasterId, $event_id);
//                $objItems->where('master_items_id', $intMasterId)
//                         ->where('events_id', $event_id)
//                         ->delete();
            }else{
//                $deletedItems[] = $row_id;
//                $objItems->where('id', $row_id)
//                         ->where('events_id', $event_id)
//                         ->delete();
            }
            $objPrcZoneDs = new PriceZonesDataSource();
            $objPrcZoneDs->deleteVersionsByItemsId($row_id, $intMasterId, $event_id);

            /**
             * Track the activity logs items & linked items details
             */
            $itemCnt = isset($countBy[0]) ? $countBy[0] : 0;
            $linkItmCnt = isset($countBy[1]) ? $countBy[1] : 0;
            $logsData = array_merge($params, ['events_id' => $event_id, 
                                              'actions' => 'delete', 
                                              'tracking_id' => $this->unique_id, 
                                              'users_id' => $params['last_modified_by'], 
                                              'descriptions' => $itemCnt . ' Items Deleted']
                                    );
            unset($logsData['id']);
            if ($itemCnt > 0) {
                event(new ItemsActivityLogs($logsData));
            }
            if ($linkItmCnt > 0) {
                $logsData['descriptions'] = $linkItmCnt . ' Linked Items Deleted';
                $logsData['type'] = '1';
                event(new ItemsActivityLogs($logsData));
            }
            $objItems->dbCommit();
        } catch (\Exception $ex) {
            $objItems->dbRollback();
        }
        return $deletedItems;
    }
    
     function deleteRecordByItemsId($params) {
        $row_id = isset($params['id']) ? $params['id'] : 0;
        $event_id = isset($params['events_id']) ? $params['events_id'] : 0;
        $intMasterId = 0;        
        $objItems = new Items();        
        $deletedItems = $countBy = $arrPrimId = $upcNbr = [];
        $objItems->dbTransaction();
        
        try {
            $arInfo = $objItems->where('id', $row_id)->get()->toArray();                              
            $searchKeyArray = ['searched_item_nbr', 'upc_nbr', 'itemsid'];           
            if(in_array($params['item_key'], $searchKeyArray)){
                $itemkey = $params['item_key'];
            }
            if (isset($arInfo[0]) && !empty($arInfo[0])) {
                $dbData = $objItems->where($itemkey, $arInfo[0][$itemkey])->where('events_id', $event_id)->get(['id', 'upc_nbr'])->toArray();
                if (!empty($dbData)) {
                    foreach ($dbData as $row) {
                        $arrPrimId[] = $row['id'];
                        $upcNbr[] = $row['upc_nbr'];
                    }
                }
            }
            
            if(!empty($arrPrimId)){
                $deletedItems = $arrPrimId;
                $objItems->whereIn('id', $arrPrimId)->delete();                
                $objEdit = new \CodePi\Base\Eloquent\ItemsEditable();
                $objEdit->whereIn('items_id', $arrPrimId)->delete();                
                $objNonEdit = new \CodePi\Base\Eloquent\ItemsNonEditable();
                $objNonEdit->whereIn('items_id', $arrPrimId)->delete();
            }
            
            $objPrcZoneDs = new PriceZonesDataSource();
            $objPrcZoneDs->deleteVersionsByItemsId($arrPrimId, $intMasterId = 0, $event_id);
            unset($arrPrimId);
            
            /**
             * Track the activity logs items & linked items details
             */
//            $itemCnt = isset($countBy[0]) ? $countBy[0] : 0;
//            $linkItmCnt = isset($countBy[1]) ? $countBy[1] : 0;
//            $logsData = array_merge($params, ['events_id' => $event_id, 
//                                              'actions' => 'delete', 
//                                              'tracking_id' => $this->unique_id, 
//                                              'users_id' => $params['last_modified_by'], 
//                                              'descriptions' => $itemCnt . ' Items Deleted']
//                                    );
//            unset($logsData['id']);
//            if ($itemCnt > 0) {
//                event(new ItemsActivityLogs($logsData));
//            }
//            if ($linkItmCnt > 0) {
//                $logsData['descriptions'] = $linkItmCnt . ' Linked Items Deleted';
//                $logsData['type'] = '1';
//                event(new ItemsActivityLogs($logsData));
//            }
            $objItems->dbCommit();
        } catch (\Exception $ex) {            
            $objItems->dbRollback();
        }
        return $deletedItems;
    }
    /**
     * 
     * @param type $intMasterId
     * @param type $intEventId
     * @return type
     */
    function getDeletedItemsByItemId($intMasterId, $intEventId) {
        $arrDeletedIds = [];
        $objCopyDS = new CopyItemsDataSource();
        $item = $objCopyDS->getItemListById(['master_items_id' => $intMasterId, 'event_id' => $intEventId]);
        $arrItemData = $this->doArray($item);
        if (!empty($arrItemData)) {
            foreach ($arrItemData as $data) {
                $arrDeletedIds[] = $data['id'];
            }
        }
        return $arrDeletedIds;
    }
    /**
     * 
     * @param type $intMasterId
     * @param type $intEventId
     * @return type
     */
//    function getItemsCountByItemsType($intMasterId, $intEventId) {
//        $objItems = new Items();
//        $countBy = [];
//        $dbResult = $objItems->select('items_type')
//                             ->selectRaw('count(*) as cnt')
//                             ->where('master_items_id', $intMasterId)->where('events_id', $intEventId)
//                             ->groupby('items_type')
//                             ->get(['items_type', 'cnt'])
//                             ->toArray();
//        if (!empty($dbResult)) {
//            foreach ($dbResult as $count) {
//                $countBy[$count['items_type']] = $count['cnt'];
//            }
//        }
//        return $countBy;
//    }
    function getItemsCountByItemsType($intMasterId, $intEventId) {
        $objItems = new Items();
        $countBy = [];
        $dbResult = $objItems->select('items_type')
                             ->selectRaw('count(*) as cnt')
                             ->where('master_items_id', $intMasterId)->where('events_id', $intEventId)
                             ->groupby('items_type')
                             ->get(['items_type', 'cnt'])
                             ->toArray();
        if (!empty($dbResult)) {
            foreach ($dbResult as $count) {
                $countBy[$count['items_type']] = $count['cnt'];
            }
        }
        return $countBy;
    }

    /**
     * Get only users edited value from table
     * @param type $command
     * @return array
     */
    function getUserFieldsData($command) {
        if (!empty($command->id)) {
            $columnArray = $userEditData = $result = [];
            $objItemDS = new ItemsDataSource();
            $columnName = $objItemDS->getItemDefaultHeaders($type = 0);
            foreach ($columnName as $key => $value) {
                if ($value['column_source'] == 'USER') {
                    $columnArray[] = $value['column'];
                }
            }
            $command->event_id = $command->events_id;
            $dbItems = $objItemDS->getItemsGridData($command);            
            foreach ($dbItems['objResult'] as $row) {
                $userData = (array) $row;
            }
            if (!empty($userData)) {
                foreach ($columnArray as $column) {
                    $result[$column] = $userData[$column];
                }
                unset($result['acitivity'], $result['searched_item_nbr'], $result['advertised_retail'], $result['price_id']);
            }
        }
        return $result;
    }

    /**
     * Appen and Replace the Items values 
     * 
     * @param object $command
     * @param object $command->action 1->Append; 2->Replace
     * @return boolean
     */
    function appendReplaceItems($command) {

        $params = $command->dataToArray();

        if (isset($params['action'])) {
            if ($params['action'] == 1) {
                $response = $this->appendItems($command);
            } else if ($params['action'] == 2) {
                $response = $this->replaceItems($command);
            } else {
                $response = ['status' => false];
            }
        }
        return $response;
    }

    /**
     * Do validations for append/replace
     * 
     * @param object $command
     * @return array
     */
    function isExistsItemsByEvents($command) {

        $objItems = new Items();
        $isExists = [];
        $data = $command->dataToArray();
        
        if (empty($data['events_id'])) {            
            $isExists = ['status' => false, 'error_msg' => ' Events ID Should not be empty.'];
            return $isExists;
        }
        $labelArray = ['searched_item_nbr' => 'Item Nbr',
            'upc_nbr' => 'UPC',
            'fineline_number' => 'FineLine',
            'plu_nbr' => 'PLU',
            'itemsid' => 'Dotcom ItemID'
        ];
        $isExistsLable = $labelArray[$data['item_key']];
        if (empty(trim($data['item_value']))) {
            $isExists = ['status' => false, 'error_msg' => $isExistsLable . ' Should not be empty.'];
            return $isExists;
        }
        $count_of_items = $objItems->dbTable('i')
                ->where('events_id', $data['events_id'])
                ->where($data['item_key'], trim($data['item_value']))
                ->where('items_type', '0')
                ->count();


        if ($count_of_items > 0) {
            $isExists = ['status' => false, 'error_msg' => $isExistsLable . ' Number Already Exists.'];
            return $isExists;
        }

        /**
         * Append/Replace Check the given search values exists or not in Iqs Api
         */
        $objIQS = new ItemsIQSRequest([$data['item_value']], $data['item_key']);
        $objIQS->pullItemsFromIQSApi();
        //$objEmiApiDataSource = new EmiApiDataSource();
        //$objEmiApiDataSource->getApiDataPull([$data['item_value']],$data['item_key']);         

        $data['item_nbr'] = [$data['item_value']];
        $data['search_key'] = $data['item_key'];
        $objCommand = new GetMasterItems($data);
        $cmdResponse = CommandFactory::getCommand($objCommand);
        if (empty($cmdResponse)) {
            $isExists = ['status' => false, 'error_msg' => 'No Record Found in Master Items'];
            return $isExists;
        }
    }

}
