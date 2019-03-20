<?php

namespace CodePi\SyncItems\DataSource;

use CodePi\Base\Eloquent\Items;
use CodePi\Base\Eloquent\ItemsNonEditable;
use CodePi\Base\Eloquent\ItemsEditable;
use CodePi\Base\Eloquent\MasterItems;
use App\Events\ReSyncItems;
use App\Events\ItemActions;
use App\Events\CheckIqsUpdateAvailability;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Eloquent\LogProcess;
use CodePi\Items\Utils\ItemsIQSRequest;
use Illuminate\Support\Facades\Log;
use CodePi\ItemsActivityLog\Logs\ActivityLog;

class SyncDataSource {

    /**
     *
     * @var type 
     */
    private $unique_id;

    function __construct() {
        $this->unique_id = mt_rand() . time();
    }

    /**
     * Re-sync the items from masters
     * @param object $command
     * @return array
     */
    function reSyncItems($command) {
        \DB::beginTransaction();
        $status = false;
        $message = '';
        $saveIds = [];
        try {

            $params = $command->dataToArray();
            $syncData = $this->prepareSyncData($params);
            $requestCount = count($syncData);
            $users_id = isset($params['users_id']) ? $params['users_id'] : $params['last_modified_by'];

            if (!empty($syncData)) {
                $i = 0;
                foreach ($syncData as $data) {
                    $data['tracking_id'] = $this->unique_id . '-0';
                    if ($i == 0) {
                        $arrData = ['users_id' => $users_id,
                            'events_id' => PiLib::piEncrypt($params['event_id']),
                            'progress' => 0,
                            'count' => $i,
                            'total' => $requestCount,
                            'message' => $i . ' Items Sync',
                            'status' => false
                        ];
                        event(new ReSyncItems($arrData));
                    }

                    $objItems = new Items();
                    $saveDetails = $objItems->saveRecord($data);
                    $saveIds[] = $saveDetails->id;
                    /**
                     * Unset the Items table primary id
                     */
                    unset($data['id']);
                    $objEdit = new ItemsEditable();
                    $prim_edit_id = $objEdit->where('items_id', $data['items_id'])->first();
                    $data['id'] = $prim_edit_id->id;
                    $objEdit->saveRecord($data);
                    /**
                     * Unset primary id of items editable table
                     */
                    unset($data['id']);
                    $objNonEdit = new ItemsNonEditable();
                    $prim_nonedit_id = $objNonEdit->where('items_id', $data['items_id'])->first();
                    $data['id'] = $prim_nonedit_id->id;
                    $objNonEdit->saveRecord($data);

                    $i++;
                    $progress = ($i * 100 / $requestCount);
                    if ($i == $requestCount) {
                        $status = true;
                        $message = $i . ' Items Re-synced successfully';
                    } else {
                        $status = false;
                        $message = 'Failed to re-sync items,try agian.';
                    }
                    $arrData = ['users_id' => $users_id,
                        'events_id' => PiLib::piEncrypt($params['event_id']),
                        'progress' => $progress,
                        'count' => $i,
                        'total' => $requestCount,
                        'message' => $i . ' Items Sync',
                        'status' => $status,
                        'response' => $this->getUpdatedItemsRow($params)
                    ];

                    event(new ReSyncItems($arrData));
                }
                /**
                 * Insert activity logs
                 */
                $objLogs = new ActivityLog();
                $logsData = $objLogs->setActivityLog(array('events_id' => $params['event_id'], 'actions' => 'sync', 'tracking_id' => $this->unique_id, 'users_id' => $params['created_by'], 'count' => count($saveIds), 'type' => '0'));
                $objLogs->updateActivityLog();
                unset($logsData);
            }
            \DB::commit();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();
            $message = $ex->getMessage();
            \DB::rollback();
        }
        unset($saveIds);

        return ['status' => $status, 'items_id' => $params['item_id'], 'message' => $message];
    }

    /**
     * Preapare data for sync items
     * @param array $params
     * @return array
     */
    function prepareSyncData($params) {

        $masterIds = $this->getMasterIds($params['item_id']);
        $synData = $this->getMasterItemsById($masterIds);

        $arrData = [];
        if (!empty($params['item_id']) && !empty($synData)) {
            unset($params['item_id'], $params['id'], $params['date_added'], $params['gt_date_added'], $params['created_by']);
            foreach ($masterIds as $key => $val) {
                if (isset($synData[$val]))
                    $arrData[] = array_merge(array_merge(['id' => $key, 'items_id' => $key], $synData[$val]), $params);
            }
        }

        return $arrData;
    }

    /**
     * Find master items id from items table
     * @param type $itemIds
     * @return array
     */
    function getMasterIds($itemIds) {
        $masterIds = [];
        $objItems = new Items();
        $dbResult = $objItems->whereIn('id', $itemIds)->where('items_type', '0')->get(['master_items_id', 'id'])->toArray();
        foreach ($dbResult as $row) {
            $masterIds[$row['id']] = $row['master_items_id'];
        }
        return $masterIds;
    }

    /**
     * get master items by master id
     * @param array $master_id
     * @return array
     */
    function getMasterItemsById($master_id) {
        $syncData = [];
        $objMasterItems = new MasterItems();
        $master_id = is_array($master_id) ? $master_id : [$master_id];
        $dbResult = $objMasterItems->whereIn('id', $master_id)->get()->toArray();

        $apiCoulmns = $this->setApiColumns();
        if (!empty($dbResult)) {

            foreach ($dbResult as $values) {
                foreach ($apiCoulmns as $key) {
                    if (isset($values[$key])) {
                        $syncData[$values['id']]['master_items_id'] = $values['id'];
                        $syncData[$values['id']]['item_sync_status'] = '0';
                        $syncData[$values['id']][$key] = $values[$key];
                    }
                }
            }
        }

        return $syncData;
    }

    /**
     * Get linked items data from master items table
     * @param array $masterIds
     * @return array
     */
    function getLinkItemsFromMasterItemsById($masterIds) {
        $objMasterItems = new MasterItems();
        $result = $objMasterItems->whereIn('parent_id', $masterIds)->get()->toArray();
        $linkedItmColumns = $this->setLinkedItemsApiColumns();
        $syncLinkItmData = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                foreach ($linkedItmColumns as $col) {
                    if (isset($row[$col])) {
                        $syncLinkItmData[$row['parent_id']][$row['searched_item_nbr']]['master_items_id'] = $row['parent_id'];
                        $syncLinkItmData[$row['parent_id']][$row['searched_item_nbr']][$col] = $row[$col];
                    }
                }
            }
        }

        return $syncLinkItmData;
    }

    /**
     * Do Re-sync for Linked items    
     * @param array $params
     */
    function reSyncLinkedItemsData($params) {

        $masterIds = $this->getMasterIds($params['item_id']);
        if (!empty($masterIds)) {
            $arrData = $this->getLinkItemsFromMasterItemsById($masterIds);
            unset($params['item_id'], $params['id'], $params['date_added'], $params['gt_date_added'], $params['created_by']);
            foreach ($arrData as $key1 => $row) {
                foreach ($row as $key2 => $values) {
                    $objItems = new Items();
                    $primId = $objItems->where('events_id', $params['event_id'])->where('master_items_id', $key1)->where('searched_item_nbr', $key2)->where('items_type', '1')->first();
                    if (!empty($primId)) {
                        $values['id'] = $primId->id;
                        $values['items_id'] = $primId->link_item_parent_id;
                        $data = array_merge($params, $values);
                        $objItems = new Items();
                        $saveDetails = $objItems->saveRecord($data);
                        /**
                         * Unset the Items table primary id
                         */
                        unset($data['id']);
                        $objEdit = new ItemsEditable();
                        $prim_edit_id = $objEdit->where('items_id', $data['items_id'])->first();
                        $data['id'] = $prim_edit_id->id;
                        $objEdit->saveRecord($data);
                        /**
                         * Unset primary id of items editable table
                         */
                        unset($data['id']);
                        $objNonEdit = new ItemsNonEditable();
                        $prim_nonedit_id = $objNonEdit->where('items_id', $data['items_id'])->first();
                        $data['id'] = $prim_nonedit_id->id;
                        $objNonEdit->saveRecord($data);
                    }
                }
            }
        }
    }

    /**
     * 
     * @return array
     */
    function setLinkedItemsApiColumns() {
        return [
            'item_file_description',
            'cost',
            'base_unit_retail',
            'supplier_nbr',
            'searched_item_nbr'
        ];
    }

    /**
     * Assign only api columns
     * @return array
     */
    function setApiColumns() {

        return [
            'landing_comment',
            'size',
            'buyer_user_id',
            'signing_description',
            'acctg_dept_nbr',
            //'sbu',
            'dept_description',
            'category_description',
            'items_status_code',
            'itemsid',
            'offers_id',
            'season_year',
            'landing_url',
            'item_image_url',
            'item_file_description',
            'dotcom_description',
            'marketing_description',
            'cost',
            'base_unit_retail',
            'dotcom_price',
            'supplier_nbr',
            'brand_name',
            'gtin_nbr',
            'fineline_number',
            'searched_item_nbr',
            'upc_nbr',
            'made_in_america'
        ];
    }

    /**
     * Get after sync getting updated rows
     * @param object $command
     * @return array
     */
    function getUpdatedItemsRow($params) {
        $data['items_id'] = $params['item_id'];
        $data['event_id'] = PiLib::piEncrypt($params['event_id']);
        $objCommand = new GetItemsList($data);
        $cmdResponse = CommandFactory::getCommand($objCommand);
        $arrResponse = $cmdResponse['items'];
        return $arrResponse;
    }

    /**
     * Check update availability from api
     * @param array $params
     * @return array
     */
    function checkItemsUpdateAvailability($params) {
        
        $status = false;
        $message = '';
        $searchArray = $response = $masterIds = [];
        $BulkImportItemsDsObj = new \CodePi\Import\DataSource\BulkImportItemsDs;

        try {
            if (isset($params['item_id']) && is_array($params['item_id'])) {

                $objItems = new Items();
                $importSource = $objItems->whereIn('id', $params['item_id'])->get(['id', 'master_items_id', 'searched_item_nbr', 'upc_nbr', 'items_import_source'])->toArray();
                foreach ($importSource as $source) {
                    //if($source['items_import_source'] == '1'){
                    if (!empty($source['searched_item_nbr'])) {
                        $searchArray[$source['id']] = ['search_key' => 'searched_item_nbr', 'items_value' => $source['searched_item_nbr']];
                    } else if (!empty($source['upc_nbr'])) {
                        $searchArray[$source['id']] = ['search_key' => 'upc_nbr', 'items_value' => $source['upc_nbr']];
                    } else {
                        $searchArray[$source['id']] = [];
                    }
                    //} 
                }
//                    if (!empty($masterIds)) {
//                        $objMasterItem = new MasterItems();
//                        $master_result = $objMasterItem->whereIn('id', $masterIds)->get(['is_primary', 'searched_item_nbr', 'itemsid'])->toArray();
//
//                        foreach ($master_result as $values) {
//                            if (isset($values['is_primary'])) {
//                                if ($values['is_primary'] == '1') {
//                                    $searchArray[] = ['search_key' => 'itemsid', 'items_value' => $values['itemsid']];
//                                } else {
//                                    $searchArray[] = ['search_key' => 'searched_item_nbr', 'items_value' => $values['searched_item_nbr']];
//                                }
//                            }
//                        }
//                    }

                $requestCount = count($searchArray);
                $users_id = isset($params['users_id']) ? $params['users_id'] : $params['last_modified_by'];
                $i = 0;
                $updateAvailabilityCnt = 0;
                foreach ($searchArray as $key => $val) {
                    if ($i == 0) {

                        $arrData = ['users_id' => $users_id,
                            'events_id' => PiLib::piEncrypt($params['event_id']),
                            'progress' => 0,
                            'count' => $i,
                            'total' => $requestCount,
                            'message' => $i . ' Items having update availability',
                            'status' => false
                        ];

                        event(new CheckIqsUpdateAvailability($arrData));

                    }


                    if (!empty($val)) {
                        /**
                         * Api call for pull the data from iqs to master table
                         */

                        $objIQS = new ItemsIQSRequest([$val['items_value']], $val['search_key']);
                        $objIQS->pullItemsFromIQSApi();

                    }
                    $status_chk = false;
                    $i++;
                    $progress = ($i * 100 / $requestCount);
                    $status = false;
                    $message = '';

                    $objItems = new Items();
                    //$updateAvailability = $objItems->whereIn('id', [$key])->where('item_sync_status', '1')->count();
                    $updateAvailability = $objItems->whereIn('id', $params['item_id'])->where('item_sync_status', '1')->count();
                    if ($updateAvailability > 0) {
                        $status_chk = true;
                        $updateAvailabilityCnt++;
                    }
                    if ($i == $requestCount) {

                        $status = true;
                        if ($updateAvailability > 0) {
                            $message = $updateAvailability . " Items having update availability";
                        } else {
                            $message = "No items available for update";
                        }
                    } else {
                        $status = false;
                        $message = 'Failed to check availability,try agian.';
                    }

                    $arrData = ['users_id' => $users_id,
                        'events_id' => PiLib::piEncrypt($params['event_id']),
                        'progress' => $progress,
                        'count' => $i,
                        'total' => $requestCount,
                        'message' => $message,
                        'status' => $status,
                        'response' => []//$this->getUpdatedItemsRowById($params)
                    ];
                    event(new CheckIqsUpdateAvailability($arrData));
                    if ($status_chk == true) {
                        $saveItemsID = $key;
                        $is_completed = ($requestCount == $i) ? true : false;
                        $arrItemInfo = $BulkImportItemsDsObj->sendDataToBroadCast(array($saveItemsID), $params['event_id'], $is_completed);
                        broadcast(new ItemActions($arrItemInfo, 'checkIqs'))->toOthers();
                    }

                }

                //$result = $this->getUpdatedItemsRow($params);                
                $response = false; //array_merge($result, ['status' => $status]);
                unset($arrData, $searchArray);
            }
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            $status = false;
            event(new CheckIqsUpdateAvailability(['users_id' => $users_id, 'status' => false, 'message' => $message, 'response' => []]));
        }

        return $response;
    }

    /**
     * Check update availability from api
     * @param array $params
     * @return array
     */
    function checkItemsUpdateAvailability_back($params) {

        Log::info('CheckIQS Process Start :' . date('Y-m-d H:i:s'));
        $status = false;      
        $message = '';
        $searchArray = $response = $masterIds = [];

        try {
            if (isset($params['item_id']) && is_array($params['item_id'])) {

                $objItems = new Items();
                $importSource = $objItems->whereIn('id', $params['item_id'])->get()->toArray();
                foreach ($importSource as $source) {
                    if ($source['items_import_source'] == '1') {
                        if (!empty($source['searched_item_nbr'])) {
                            $searchArray[] = ['search_key' => 'searched_item_nbr', 'items_value' => $source['searched_item_nbr']];
                        } else if (!empty($source['upc_nbr'])) {
                            $searchArray[] = ['search_key' => 'upc_nbr', 'items_value' => $source['upc_nbr']];
                        } else {
                            $searchArray = [];
                        }
                    } else {
                        $masterIds[] = $source['master_items_id'];
                    }
                }
                if (!empty($masterIds)) {
                    $objMasterItem = new MasterItems();
                    $master_result = $objMasterItem->whereIn('id', $masterIds)->get(['is_primary', 'searched_item_nbr', 'itemsid'])->toArray();

                    foreach ($master_result as $values) {
                        if (isset($values['is_primary'])) {
                            if ($values['is_primary'] == '1') {
                                $searchArray[] = ['search_key' => 'itemsid', 'items_value' => $values['itemsid']];
                            } else {
                                $searchArray[] = ['search_key' => 'searched_item_nbr', 'items_value' => $values['searched_item_nbr']];
                            }
                        }
                    }
                }

                $requestCount = count($params['item_id']);
                $users_id = isset($params['users_id']) ? $params['users_id'] : $params['last_modified_by'];
                $i = 0;
                foreach ($params['item_id'] as $row) {
                    if ($i == 0) {
                        Log::info('CheckIqsUpdateAvailability Events Start :' . date('Y-m-d H:i:s'));
                        $arrData = ['users_id' => $users_id,
                            'events_id' => PiLib::piEncrypt($params['event_id']),
                            'progress' => 0,
                            'count' => $i,
                            'total' => $requestCount,
                            'message' => $i . ' Items having update availability',
                            'status' => false
                        ];
                        event(new CheckIqsUpdateAvailability($arrData));
                        Log::info('CheckIqsUpdateAvailability Events Stop :' . date('Y-m-d H:i:s'));
                    }

                    foreach ($searchArray as $items) {
                        /**
                         * Api call for pull the data from iqs to master table
                         */
                        Log::info($i . ' pullItemsFromIQSApi Request Start :' . date('Y-m-d H:i:s'));
                        $objIQS = new ItemsIQSRequest([$items['items_value']], $items['search_key']);
                        $objIQS->pullItemsFromIQSApi();
                        Log::info($i . ' pullItemsFromIQSApi Request Stop :' . date('Y-m-d H:i:s'));
                    }

                    $i++;
                    $progress = ($i * 100 / $requestCount);
                    if ($i == $requestCount) {
                        Log::info('CheckIqsUpdateAvailability Events Start :' . date('Y-m-d H:i:s'));
                        $status = true;
                        $objItems = new Items();
                        $updateAvailability = $objItems->whereIn('id', $params['item_id'])->where('item_sync_status', '1')->count();
                        if ($updateAvailability > 0) {
                            $message = $updateAvailability . " Items having update availability";
                        } else {
                            $message = "No items available for update";
                        }
                    } else {
                        $status = false;
                        $message = 'Failed to check availability,try agian.';
                    }

                    $arrData = ['users_id' => $users_id,
                        'events_id' => PiLib::piEncrypt($params['event_id']),
                        'progress' => $progress,
                        'count' => $i,
                        'total' => $requestCount,
                        'message' => $message,
                        'status' => $status,
                        'response' => $this->getUpdatedItemsRow($params)
                    ];
                    event(new CheckIqsUpdateAvailability($arrData));
                    Log::info('CheckIqsUpdateAvailability Events Stop :' . date('Y-m-d H:i:s'));
                }

                $result = $this->getUpdatedItemsRow($params);
                $response = array_merge($result, ['status' => $status]);
                unset($arrData, $searchArray);
            }
        } catch (\Exception $ex) {
            $message = $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();
            Log::info($message . '-' . date('Y-m-d H:i:s'));
            $status = false;
            event(new CheckIqsUpdateAvailability(['users_id' => $users_id, 'status' => false, 'message' => $message, 'response' => []]));
        }
        Log::info('CheckIQS Process Stop :' . date('Y-m-d H:i:s'));
        return $response;
    }

    /**
     * Cron function to update the master items data from iqs 
     */
    function doCronUpdateMasterDataFromIQS() {
        $data = ['type' => 'iqsmasterupdate', 'description' => 'Start :: ' . date('Y-m-d H:i:s'), 'attachments_id' => 0];
        $this->saveCronLogs($data);
        set_time_limit(0);
        $objMasterItems = new MasterItems();
        $sql = "select count(*) as cnt from master_items where parent_id = 0 limit 1";
        $totalCount = $objMasterItems->dbSelect($sql);
        $process_count = 0;
        if (isset($totalCount[0]) && isset($totalCount[0]->cnt) && $totalCount[0]->cnt > 0) {
            try {
                for ($i = 0; $i <= $totalCount[0]->cnt; $i = $i + 50) {
                    $result = $objMasterItems->where('parent_id', 0)->offset($i)->limit(50)->get(['searched_item_nbr', 'itemsid', 'is_primary'])->toArray();

                    foreach ($result as $row) {
                        if ($row['is_primary'] == '1') {
                            $objIQS = new ItemsIQSRequest([$row['itemsid']], 'itemsid');
                            $objIQS->pullItemsFromIQSApi();
                        } else {
                            $objIQS = new ItemsIQSRequest([$row['searched_item_nbr']], 'searched_item_nbr');
                            $objIQS->pullItemsFromIQSApi();
                        }
                        $process_count++;
                    }
                }
                $message = $process_count . " items successfully updated";
                $status = true;
            } catch (\Exception $ex) {
                $message = $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();
                $status = false;
            }
        } else {
            $message = "No items to update";
            $status = false;
        }
        $data = ['type' => 'iqsmasterupdate', 'description' => 'Stoped :: ' . date('Y-m-d H:i:s'), 'attachments_id' => 0];
        $this->saveCronLogs($data);

    }

    /**
     * 
     * @param type $data
     */
    function saveCronLogs($data) {
        \DB::beginTransaction();
        try {
            $obj = new LogProcess();
            $obj->saveRecord($data);
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
    }

}
