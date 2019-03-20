<?php

namespace CodePi\Items\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Items;
use CodePi\Base\Eloquent\Events;
use GuzzleHttp;
use CodePi\Base\Eloquent\ItemsHeaders;
use CodePi\Base\Eloquent\ItemsEditable;
use CodePi\Base\Eloquent\ItemsNonEditable;
use CodePi\Base\Eloquent\ItemsGroupsItems;
use CodePi\Base\Eloquent\ItemsGroups;

use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\DataSource\ItemsDataSource;
use App\Events\UpdateEventStatus;
#use CodePi\Items\Commands\GetItemsList;
#use CodePi\ItemsActivityLog\DataSource\ItemsActivityLogsDs;
#use App\Events\ItemsActivityLogs;
#use CodePi\Api\Commands\GetMasterItems;
#use CodePi\Api\DataSource\EmiApiDataSource;
use CodePi\Base\Eloquent\MasterItems;
use CodePi\Base\Eloquent\Users;
use CodePi\Items\DataSource\GroupedDataSource;
use CodePi\Items\DataSource\PriceZonesDataSource;
use CodePi\Channels\DataSource\ChannelsDataSource;
use DB;
use CodePi\Events\DataSource\EventsDataSource;
use CodePi\Import\DataSource\BulkImportItemsDs;
use CodePi\Base\Eloquent\Settings;
/**
 * Handle the linked items save ,listing and search
 */
class LinkedItemsDataSource {

    /**
     * Get only linked item columns 
     * @return type
     */
    function getLinkedItmColumn() {
        $getColumn = [];
        $objItemsDs = new ItemsDataSource();
        $result = $objItemsDs->getItemDefaultHeaders($type = 2);
        if ($result) {
            $getColumn = $result;
        }

        return $getColumn;
    }

    /**
     * This method will handle the list of linked items and search
     * @param type $command
     * @return type object
     */
    function getLinkedItemList($command) {
        $departments_id = 0;
        $params = $command->dataToArray();
        $users_id = (isset($params['users_id']) && $params['users_id'] != 0) ? $params['users_id'] : $params['last_modified_by'];
        $objUsers = new Users;
        $usrObj = $objUsers->where('id', $users_id)->get(['departments_id']);
        $departments_id = $usrObj[0]->departments_id;
        $getColumns = $this->getLinkedItmColumn();

        $column = $searchColumn = [];
        foreach ($getColumns as $key => $value) {
            $column[] = 'trim(' . $value['aliases_name'] . '.' . $key . ') as ' . $key;
            $searchColumn[] = $value['aliases_name'] . '.' . $key;
        }
        $columnName = implode($column, ',');

        $isAnd = true;
        $objItems = new Items();
        $objItemsDs = new ItemsDataSource();
        $permissions = $objItemsDs->getAccessPermissions($users_id);
        $permissions['departments_id'] = $departments_id;

        $objResult = $objItems->dbTable('i')
                              ->leftJoin('items_editable as ie', 'ie.items_id', '=', 'i.id')
                              ->leftJoin('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                              ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                              ->select('i.id', 'is_excluded', 'i.items_import_source')
                              ->selectRaw($columnName)
                              ->where(function ($query) use ($params) {
                                    if (isset($params['event_id']) && trim($params['event_id']) != '') {
                                        $query->where('i.events_id', $params['event_id']);
                                    }
                              })->where(function ($query) use ($searchColumn, $params, $isAnd) {
                                    if (isset($params['search']) && trim($params['search']) != '') {
                                        foreach ($searchColumn as $key) {
                                            if ($isAnd) {
                                                $query->where($key, 'like', '%' . $params['search'] . '%');
                                                $isAnd = false;
                                            } else {
                                                $query->orWhere($key, 'like', '%' . $params['search'] . '%');
                                            }
                                        }
                                    }
                              })->where(function ($query) use ($params) {
                                    if (isset($params['export_option'])) {
                                        if ($params['export_option'] == '1' && $params['is_export'] == true) {
                                            $query->whereRaw('i.is_excluded = "0" OR i.is_excluded = "1" ');
                                        } else if ($params['export_option'] == '2' && $params['is_export'] == true) {
                                            $query->whereRaw('i.is_excluded = "0"');
                                        }
                                    }
                              })->where(function ($query) use ($params) {
                                    if (isset($params['itemsListUserId']) && !empty($params['itemsListUserId'])) {
                                        $query->where('i.created_by', $params['itemsListUserId']);
                                    }
                              })->where(function ($query) use ($params) {
                                    if (isset($params['department_id']) && !empty($params['department_id'])) {
                                        $query->where('u.departments_id', $params['department_id']);
                                    }
                              })->where('items_type', '1')
                                ->where(function($query) use ($permissions, $params) {
                                    if (isset($permissions['items_access']) && ($permissions['items_access'] == '6' || $permissions['items_access'] == '4' || $permissions['items_access'] == '5')) {

                                    } else if (isset($permissions['items_access']) && ($permissions['items_access'] == '2' || $permissions['items_access'] == '3')) {
                                        $query->where('u.departments_id', $permissions['departments_id']);
                                    } else {
                                        $query->where('i.created_by', $params['last_modified_by']);
                                    }
                              })->groupBy('i.id');
                              if (isset($params['order']) && (isset($params['sort']) && !empty($params['sort']))) {
                                    $aliase = $objItemsDs->findTableAliaseName($params['order']);
                                    $data_type = (isset($aliase['type']) && $aliase['type'] == 'numeric') ? 'unsigned' : 'char';
                                    $sort = $params['sort'];
                                    $objResult = $objResult->orderByRaw('cast(' . $aliase['aliase'] . '.' . $params['order'] . ' as ' . $data_type . ') ' . $sort . '');
                              } else {
                                    $objResult = $objResult->orderBy('i.last_modified', 'desc');
                              }

                              if (isset($params['page']) && !empty($params['page']) && $params['is_export'] == false) {
                                    $objResult = $objResult->paginate($params['perPage']);
                              } else {
                                    $objResult = $objResult->get();
                              }

        return $objResult;
    }

    /**
     * This method will handle the list of linked items and search
     * @param type $command
     * @return type object
     */
    function getLinkedItemListByParent($command) {

        $params = $command->dataToArray();
        $getColumns = $this->getLinkedItmColumn();
        $column = $searchColumn = [];
        foreach ($getColumns as $key => $value) {
            $column[] = 'trim(' . $value['aliases_name'] . '.' . $key . ') as ' . $key;
            $searchColumn[] = $value['aliases_name'] . '.' . $key;
        }
        $columnName = implode($column, ',');

        $isAnd = true;
        $objItems = new Items();
        if (isset($params['parent_id']) && !empty($params['parent_id'])) {
            $upcNbr = $this->getUpcNbrByParentItems($params['parent_id']);
        }

        $objResult = $objItems->dbTable('i')
                              ->leftJoin('items_editable as ie', 'ie.items_id', '=', 'i.id')
                              ->leftJoin('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                              ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                              ->select('i.id', 'is_excluded', 'master_items_id', 'upc_nbr')
                              ->selectRaw($columnName)
                              ->where(function ($query) use ($params) {
                                    if (isset($params['event_id']) && trim($params['event_id']) != '') {
                                        $query->where('i.events_id', $params['event_id']);
                                    }
                              })->where(function ($query) use ($params, $upcNbr) {
                                    if (isset($params['parent_id']) && !empty($params['parent_id'])) {
                                        $query->where('i.upc_nbr', $upcNbr);
                                    }
                              })->where(function ($query) use ($searchColumn, $params, $isAnd) {
                                    if (isset($params['search']) && trim($params['search']) != '') {
                                        foreach ($searchColumn as $key) {
                                            if ($isAnd) {
                                                $query->where($key, 'like', '%' . $params['search'] . '%');
                                                $isAnd = false;
                                            } else {
                                                $query->orWhere($key, 'like', '%' . $params['search'] . '%');
                                            }
                                        }
                                    }
                              })->where('items_type', '1');
                              
                            if (isset($params['order']) && (isset($params['sort']) && !empty($params['sort']))) {
                                $objItemsDs = new ItemsDataSource();
                                $aliase = $objItemsDs->findTableAliaseName($params['order']);
                                $data_type = (isset($aliase['type']) && $aliase['type'] == 'numeric') ? 'unsigned' : 'char';
                                $sort = $params['sort'];
                                $objResult = $objResult->orderByRaw('cast(' . $aliase['aliase'] . '.' . $params['order'] . ' as ' . $data_type . ') ' . $sort . '');
                            } else {
                                $objResult = $objResult->orderBy('i.last_modified', 'desc');
                            }
                            $objResult = $objResult->get();

        return $objResult;
    }
    
    /**
     * This method will handle the move linked items to result items
     * @param Object $command
     * @return Array
     */
    function moveLinkedItems($command) {
        
        $command->id = null;
        $command->is_price_req = 1;
        $command->userEditable = array();
        $params = $command->dataToArray(); 
        $createdInfo = $command->getCreatedInfo();
        $status = false;
        $itemNbr = $sameUpcItems = [];
        
        if (is_array($params['item_id']) && !empty($params['item_id'])) {
            
            $objItems = new Items();
            $dbResult = $objItems->whereIn('id', $params['item_id'])->get(['searched_item_nbr', 'id', 'master_items_id'])->toArray();
            foreach ($dbResult as $row) {
                $itemNbr[] = $row['searched_item_nbr'];
            }            
            $command->items = $itemNbr;
            $command->search_key = 'searched_item_nbr';
                   
            $objItemDs = new ItemsDataSource();
            DB::beginTransaction();
            try {
                /**
                 * Same Upc related items id, to update the result items data, while move to result items
                 */
                $sameUpcItems = $this->getSameUpcNbrItemsId($params['item_id'], $params['event_id']);    
                /**
                 * Get selected linked items data, to insert as a result items, if items are not available in api or master
                 */
                $objData = $this->getLinkedItemsByPrimId(['link_item_id' => $params['item_id'], 'event_id' => $params['event_id']]);                                
                /**
                 * Delete selected row
                 */
                $objItems->whereIn('id', $params['item_id'])->delete();
                $objEdit = new ItemsEditable();            
                $objEdit->whereIn('items_id', $params['item_id'])->delete();
                $objNonEdit = new ItemsNonEditable();
                $objNonEdit->whereIn('items_id', $params['item_id'])->delete();
                /**
                 * if API settings is off, localy data will add
                 */
                $apiSettings = Settings::key('stop_iqs_api');                
                if(empty($apiSettings)){
                    $saveResponse = $objItemDs->saveItems($command);
                }else{
                    
                    $saveResponse = $this->saveNonExitsItems($objData, $itemNbr, $params['event_id'], $createdInfo);
                }
                if (isset($params['parent_id']) && !empty($params['parent_id'])) {
                    $objGroupDs = new GroupedDataSource();
                    $objGroupDs->saveMovedItemsInGroup($saveResponse, $params['parent_id']);
                }
                $status = isset($saveResponse['items_id']) && !empty($saveResponse['items_id']) ? true : false;                
                DB::commit();
            } catch (\Exception $ex) {
                $status = false;
                $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
                CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
                DB::rollback();
            }
            
            /**
             * Update event status
             */
            $isPublish = $objItemDs->isPublishedEvents($params['event_id']);
            if ($isPublish == 0) {
                $params['statuses_id'] = 3;
            } else {
                $params['statuses_id'] = 2;
            }
            event(new UpdateEventStatus($params['statuses_id'], $params['event_id']));
            unset($params['statuses_id']);
        }        
        $sameUpcItems = !empty($sameUpcItems) ? $sameUpcItems : $params['parent_id'];
        return  ['status' => $status, 'items_id' => isset($saveResponse['items_id']) ? $saveResponse['items_id'] : [], 'sameUpcItemsId' => $sameUpcItems];
    }
    /**
     * This method will handle the , if items not exits from master, 
     * it will add to be a new item along with limited columns data
     * @param type $coll
     * @param type $itemNbr
     * @param type $intEventId
     */
    function saveNonExitsItems($coll, $itemNbr, $intEventId, $createdInfo) {

        $arrItemsId = [];
        DB::beginTransaction();
        try {
            if ($coll) {
                $objItemDs = new ItemsDataSource();
                $arrData = $objItemDs->doArray($coll);
                $objEventsDs = new EventsDataSource();
                $historicalDates = $objEventsDs->getHistoricalReferenceDate($intEventId);
                $aprimoDetails = $objEventsDs->getAprimoDetails($intEventId);
                foreach ($arrData as $data) {
                    if (in_array($data['searched_item_nbr'], $itemNbr)) {
                        $data['id'] = null;
                        $data['events_id'] = $intEventId;                        
                        $objBulkDs = new BulkImportItemsDs();
                        $data['master_items_id'] = $objBulkDs->insertIntoMasterData($data);
                        $objIems = new Items();
                        $saveID = $objIems->saveRecord(array_merge($data, $createdInfo));
                        $data['items_id'] = $saveID->id;
                        $data['versions'] = 'No Price Zone found.';
                        $data['event_dates'] = $historicalDates;
                        $data['aprimo_campaign_id'] = isset($aprimoDetails['aprimo_campaign_id']) ? $aprimoDetails['aprimo_campaign_id'] : '';
                        $data['aprimo_campaign_name'] = isset($aprimoDetails['aprimo_campaign_name']) ? $aprimoDetails['aprimo_campaign_name'] : '';
                        $data['aprimo_project_id'] = isset($aprimoDetails['aprimo_project_id']) ? $aprimoDetails['aprimo_project_id'] : '';
                        $data['aprimo_project_name'] = isset($aprimoDetails['aprimo_project_name']) ? $aprimoDetails['aprimo_project_name'] : '';
                        $objItemsEdit = new ItemsEditable();
                        $objItemsEdit->saveRecord(array_merge($data, $createdInfo));
                        $objItemsNonEdit = new ItemsNonEditable();
                        $objItemsNonEdit->saveRecord(array_merge($data, $createdInfo));
                        $arrItemsId['items_id'][] = $saveID->id;
                    }
                }
            }
            DB::commit();
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            DB::rollback();
        }

        return $arrItemsId;
    }

    /**
     * 
     * @param type $value
     * @param type $searchKey
     * @return int
     */
    function checkItemsExistsInMaster($value, $searchKey) {
        $objMasterItms = new MasterItems();
        $countValue = $objMasterItms->whereIn($searchKey, $value)->where('parent_id', '0')->count();
        return $countValue;
    }   

    /**
     * Copy linked items
     * @param obj $command
     * @return boolean
     */
    function copyLinkedItems($command) {
        DB::beginTransaction();
        try {
            if (!empty($command->save_ids)) {
                $objItems = new Items();
                $objEdit = new ItemsEditable();
                $objItemNonEdit = new ItemsNonEditable();
                $objItemsDs = new ItemsDataSource();
                $params = $command->dataToArray();
                unset($params['items_id']);
                foreach ($params['save_ids'] as $item_id) {
                    $data = [];
                    $parent_id = $objItems->where('id', $item_id)->first();
                    $command->parent_id = $parent_id->copy_items_id;
                    $link_items = $this->getLinkedItemListByParent($command);
                    $command->event_id = $command->to_events_id;

                    if (!empty($link_items)) {
                        foreach ($link_items as $row) {
                            $existLinkedItems = $objItemsDs->getItemsLikedItemsByEvent($command->to_events_id, $row->upc_nbr);
                            $itemmd5 = md5($row->upc_nbr . '_' . $row->searched_item_nbr);

                            if (!in_array($itemmd5, $existLinkedItems)) {
                                unset($row->id);
                                $row->link_item_parent_id = 0;
                                $row->items_type = '1';
                                $row->events_id = $params['to_events_id'];
                                $data = array_merge($params, (array) $row);
                                if (!empty($data)) {
                                    $save_items = $objItems->saveRecord($data);
                                    $data['items_id'] = $save_items->id;
                                    $objEdit->saveRecord($data);
                                    $objItemNonEdit->saveRecord($data);
                                }
                                $existLinkedItems[] = $itemmd5;
                            }
                        }
                    }
                }
                DB::commit();
                return true;
            }
        } catch (\Exception $ex) {
           DB::rollback();
           $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
           CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
           
        }
    }
    /**
     * 
     * @param type $param
     * @param type $id
     * @return type
     */
    function getRemainingItems($param, $id) {
        
        $arrItemsId = [];
        $objItems = new Items();
        $objResult = $objItems->dbTable('i')
                              ->select('i.id')
                              ->where('i.upc_nbr', '=', $param['upc_nbr'])
                              ->where('i.events_id', '=', $param['events_id'])
                              ->where('i.items_type', '=', '0')
                              ->where('i.id', '!=', $id)
                              ->get();
        
        if (!empty($objResult)) {
            foreach ($objResult as $row) {
                $arrItemsId[] = $row->id;
            }
        }
        return $arrItemsId;
    }

    /**
     * 
     * @param type $params
     * @return type
     */
    function insertLinkedItemsRecord($params) {

        DB::beginTransaction();
        $status = false;
        $insertData = $saveIds = [];
        $affectedRow = 0;
        try {
            $objGroupDs = new GroupedDataSource();
            $arrItemsId = $objGroupDs->moveGroupedItems($params);

            if (!empty($arrItemsId)) {
                /**
                 * Get the data based on IDs
                 */
                $objCopy = new CopyItemsDataSource();
                $params['items_id'] = $arrItemsId;
                $params['event_id'] = $params['events_id'];
                $dbData = $objCopy->getItemListById($params);

                /**
                 * Convert to array
                 */
                $objIemsDs = new ItemsDataSource();
                $resultItms = $objIemsDs->doArray($dbData);
                unset($params['id'], $params['items_id'], $params['events_id'], $params['event_id']);
                if (!empty($resultItms)) {
                    /**
                     * Delete the result items, related with others tables
                     */
                    $affectedRow = $this->deletedMovedResultItems($arrItemsId);

                    foreach ($resultItms as $row) {
                        $insertData['searched_item_nbr'] = $row['searched_item_nbr'];
                        $insertData['events_id'] = $row['events_id'];
                        $insertData['item_file_description'] = preg_replace("/\r|\n/", "", $row['item_file_description']);
                        $insertData['cost'] = $row['cost'];
                        $insertData['base_unit_retail'] = $row['base_unit_retail'];
                        $insertData['supplier_nbr'] = $row['supplier_nbr'];
                        $insertData['upc_nbr'] = str_pad($row['upc_nbr'], 13, '0', STR_PAD_LEFT);
                        $insertData['items_import_source'] = $row['items_import_source'];
                        $insertData['items_type'] = '1';
                        $insertData['signing_description'] = ($row['items_import_source'] == '1') ? preg_replace("/\r|\n/", "", $row['signing_description']) : '';
                        $objItems = new Items();
                        $items = $objItems->saveRecord($insertData);
                        $insertData['items_id'] = $items->id;
                        $objEdit = new ItemsEditable();
                        $objEdit->saveRecord($insertData);
                        $objNonEdit = new ItemsNonEditable();
                        $objNonEdit->saveRecord($insertData);
                        $saveIds[] = $items->id;
                    }
                    unset($insertData);                    
                }
            }
            $status = !empty($saveIds) ? true : false;
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $status = false;
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        
        return array('items_id' => $saveIds, 'deleted_items' => !empty($affectedRow) ? $arrItemsId : [], 'status' => $status);
    }

    /**
     * 
     * @param array $arrItemsID
     */
    function deletedMovedResultItems(array $arrItemsID) {
        DB::beginTransaction();
        $affectedRow = 0;
        try {
            if (!empty($arrItemsID)) {
                $objItems = new Items();
                $affectedRow = $objItems->whereIn('id', $arrItemsID)->delete();

                $objEdit = new ItemsEditable();
                $objEdit->whereIn('items_id', $arrItemsID)->delete();

                $objNonEdit = new ItemsNonEditable();
                $objNonEdit->whereIn('items_id', $arrItemsID)->delete();

                $objGroupItems = new ItemsGroupsItems();
                $objGroupItems->whereIn('items_id', $arrItemsID)->delete();

                $objGroup = new ItemsGroups();
                $objGroup->whereIn('items_id', $arrItemsID)->delete();

                $objPrcZoneDs = new PriceZonesDataSource();
                $objPrcZoneDs->deleteVersionsByItemsId($arrItemsID, 0, 0);

                $objChannelDs = new ChannelsDataSource();
                $objChannelDs->deleteChannelsItemsAdTypes($arrItemsID);
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();            
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $affectedRow;
    }

    /**
     * Get UPC Nbr
     * @param int $intRowId
     * @return string
     */
    function getUpcNbrByParentItems($intRowId) {
        $objItems = new Items();
        $result = $objItems->where('id', $intRowId)->first();
        return $result->upc_nbr;
    }

    /**
     * Get Linked items by id
     * @param type $params['event_id']
     * @param type $params['link_item_id']
     * @return type
     */
    function getLinkedItemsByPrimId($params) {

        $getColumns = $this->getLinkedItmColumn();
        $column = $searchColumn = [];
        foreach ($getColumns as $key => $value) {
            $column[] = 'trim(' . $value['aliases_name'] . '.' . $key . ') as ' . $key;
        }

        $columnName = implode($column, ',');
        $objItems = new Items();

        $objResult = $objItems->dbTable('i')
                        ->leftJoin('items_editable as ie', 'ie.items_id', '=', 'i.id')
                        ->leftJoin('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                        ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                        ->select('i.id', 'is_excluded', 'master_items_id', 'upc_nbr')
                        ->selectRaw($columnName)
                        ->where(function ($query) use ($params) {
                            if (isset($params['event_id']) && trim($params['event_id']) != '') {
                                $query->where('i.events_id', $params['event_id']);
                            }
                        })->where(function ($query) use ($params) {
                    if (isset($params['link_item_id']) && !empty($params['link_item_id'])) {
                        if (!is_array($params['link_item_id'])) {
                            $query->where('i.id', $params['link_item_id']);
                        } else {
                            $query->whereIn('i.id', $params['link_item_id']);
                        }
                    }
                })->where('items_type', '1')->orderBy('i.id', 'asc')->get();
        return $objResult;
    }
    
    /**
     * 
     * @param array $arrLinkItemId
     * @param int $intEvnId
     * @return type
     */
    function getSameUpcNbrItemsId(array $arrLinkItemId, $intEvnId) {
        $arrSameUpcItmID = [];
        if (!empty($arrLinkItemId)) {
            $objItems = new Items();
            $arrUpcNbr = $objItems->whereIn('id', $arrLinkItemId)
                    ->where('upc_nbr', '!=', '')
                    ->selectRaw('DISTINCT upc_nbr')
                    ->orderBy('id', 'asc')
                    ->get()->toArray();
            if (!empty($arrUpcNbr)) {
                $objResult = $objItems->whereIn('upc_nbr', $arrUpcNbr)
                        ->where('events_id', $intEvnId)
                        ->where('items_type', '0')
                        ->get()->toArray();
                unset($arrUpcNbr);
                foreach ($objResult as $row) {
                    $arrSameUpcItmID[] = $row['id'];
                }
            }
        }
        return $arrSameUpcItmID;
    }

}