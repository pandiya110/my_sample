<?php

namespace CodePi\Items\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Items;
use CodePi\Items\DataSource\ItemsDataSource;
use App\Events\ItemsActivityLogs;
use CodePi\Channels\DataSource\ChannelsDataSource;
use GuzzleHttp;
use CodePi\Items\DataSource\PriceZonesDataSource;
use CodePi\Base\Eloquent\ItemsEditable;
use CodePi\Base\Eloquent\ItemsNonEditable;
use CodePi\Base\Eloquent\ChannelsItems;
use DB;
use CodePi\Base\Libraries\PiLib;
use CodePi\ItemsActivityLog\Logs\ActivityLog;
use CodePi\Items\DataSource\GroupedDataSource;
use CodePi\Base\Commands\CommandFactory;
class CopyItemsDataSource {

    private $unique_id;

    function __construct() {
        $this->unique_id = mt_rand() . time();
    }

    /**
     * Get items list by given ids
     * @param array $params
     * @return collections
     */
    function getItemListById($params) {
        $objItemDs = new ItemsDataSource();
        $itemType = isset($params['item_type']) ? $params['item_type'] : '0';
        $getColumns = $objItemDs->getItemDefaultHeaders($linked_item_type = 0);
        $column = $searchColumn = [];
        foreach ($getColumns as $key => $value) {
            $column[] = 'trim(' . $value['aliases_name'] . '.' . $key . ') as ' . $key;
            $searchColumn[] = $value['aliases_name'] . '.' . $key;
        }
        $columnName = implode($column, ',');
        $isAnd = true;
        $objItems = new Items();
        $dbResult = $objItems->dbTable('i')
                             ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                             ->join('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                             ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                             ->leftJoin('items_groups as ig', 'i.id', '=', 'ig.items_id')
                             ->leftJoin('items as child', function($join) use ($params) {
                                $join->on('i.upc_nbr', '=', 'child.upc_nbr')
                                ->where('child.items_type', '=', '1')
                                ->where('child.events_id', $params['event_id']);
                             })
                             ->select('i.id', 'i.is_excluded', 'i.is_no_record', 'i.item_sync_status', 'i.publish_status', 'i.master_items_id', 'i.created_by', 'u.departments_id', 'i.cell_color_codes', 'i.last_modified')
                             ->selectRaw($columnName)
                             ->selectRaw('count(child.upc_nbr) as link_count')
                             ->selectRaw('IF(ie.grouped_item !=\'\', 1, 0) AS isGroupedItems')
                             ->selectRaw('ig.items_id AS parentGroup')
                             ->selectRaw('(SELECT igi.items_id FROM items_groups_items AS igi WHERE igi.items_id = i.id LIMIT 1) AS childGroup')
                             ->selectRaw('(SELECT mp.name FROM master_data_options AS mp WHERE mp.id = SUBSTRING_INDEX(ie.local_sources, ":",-1)) as local_sources')
                                     
//        $dbResult = $objItems->dbTable('i')                
//                             ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
//                             ->join('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
//                             ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
//                             ->leftJoin('items as child', function($join) use ($params){ 
//                              $join->on('i.upc_nbr', '=', 'child.upc_nbr')
//                              ->where('child.items_type', '=', '1')
//                              ->where('child.events_id', $params['event_id']);
//                              }) 
//                             ->select('i.id', 'i.is_excluded', 'i.is_no_record', 'i.item_sync_status', 'i.publish_status', 'i.master_items_id', 'i.created_by', 'u.departments_id', 'i.cell_color_codes', 'i.events_id')
//                             //->selectRaw('(select count(link_item_parent_id) from items where link_item_parent_id = i.id) as link_count')
//                             ->selectRaw($columnName)
//                             ->selectRaw('count(child.upc_nbr) as link_count')
//                             ->selectRaw('IF(ie.grouped_item !=\'\', 1, 0) AS isGroupedItems')
                                     
                        ->where(function ($query) use ($params) {
                            if (isset($params['event_id']) && trim($params['event_id']) != '') {
                                $query->where('i.events_id', $params['event_id']);
                            }
                        })->where(function ($query) use ($params) {
                            if (isset($params['items_id']) && !empty($params['items_id'])) {
                                $query->whereIn('i.id', $params['items_id']);
                            }
                        })->where(function ($query) use ($params) {
                            if (isset($params['master_items_id']) && !empty($params['master_items_id'])) {
                                $query->where('i.master_items_id', $params['master_items_id']);
                            }
                        })->where('i.items_type', $itemType)
                        ->groupBy('i.id')->get();
        return $dbResult;
    }

    /**
     * Prepare data to be copied from one events to another events
     * @param array $params
     * @return array
     */
    function prepareCopyItemsData(array $params) {
        $params['event_id'] = $params['from_events_id'];
        
        /**
         * Get items list by id
         */
        $fromData = $this->getItemListById($params);        
        $objItemDs = new ItemsDataSource();
        $itemsData = $objItemDs->doArray($fromData);
        
        /**
         * Check selected items already copied or not
         */
        $alreadyCopy = $this->checkAlreadyCopiedItems($params);
        $itemsIds = $params['items_id'];
        $emptyRow = $arrayItems = $copyData = [];
        /**
         * Ignored empty rows from selected items
         */
        if (!empty($itemsData)) {
            foreach ($itemsData as $itemsRow) {
                if ($itemsRow['searched_item_nbr'] != '' || $itemsRow['upc_nbr'] != '' || $itemsRow['plu_nbr'] != '' || $itemsRow['fineline_number'] != '') {
                    $arrayItems[$itemsRow['id']] = $itemsRow;
                } else {
                    $emptyRow[] = $itemsRow['id'];
                }
            }
        }
        /**
         * Find the differnce the selected itemsid from already copied itemsid
         */
        $newCopyId = array_diff($itemsIds, $alreadyCopy);
        
        /**
         * Check the versions and get the final data to be copy from one event to another
         */
        if (!empty($arrayItems)) {
            $arrResponse = $this->checkItemsExistsByVersion($params['to_events_id'], $arrayItems, $newCopyId);
            $availableData = isset($arrResponse['availableItems']) ? $arrResponse['availableItems'] : [];  
            
            if ($availableData) {
                foreach ($availableData as $key => $rowData) {
                    if(isset($arrayItems[$key])){
                        $copyData[] = $arrayItems[$key];
                    }
                }
            }
        }
        
        $finalData = ['already_copy' => $alreadyCopy, 'exists_items' => isset($arrResponse['existsItems']) ? $arrResponse['existsItems'] : [],
                      'copyData' => $copyData, 'ignored_items' => $emptyRow
        ];
        
        return $finalData;
    }

    /**
     * check selected items already copied or not
     * @param array $params
     * @return array
     */
    function checkAlreadyCopiedItems(array $params) {
        $alreadyCopy = [];
        $itemsIds = $params['items_id'];
        $objItems = new Items();
        $result = $objItems->whereIn('copy_items_id', $itemsIds)
                           ->where('events_id', $params['to_events_id'])
                           ->get(['copy_items_id'])
                           ->toArray();
        if (isset($result[0]) && isset($result[0]['copy_items_id']) && !empty($result[0]['copy_items_id'])) {
            foreach ($result as $row) {
                $alreadyCopy[] = $row['copy_items_id'];
            }
        }
        
        return $alreadyCopy;
    }    
    /**
     * Check selected items versions already exists or not
     * @param type $eventId
     * @param array $itemsData
     * @param array $itemsId
     * @return array
     */
    function checkItemsExistsByVersion($eventId, array $itemsData, array $itemsId) {

        $isExistsVer = $versions = [];
        if (!empty($itemsId)) {
            foreach ($itemsId as $value) {
                if (isset($itemsData[$value]['id'])) {

                    $noVerfound = trim(strtolower(str_replace(' ', '', $itemsData[$value]['versions'])));
                    if ($noVerfound != 'nopricezoneavailable.' && $noVerfound != 'nopricezonefound.') {
                        if (isset($itemsData[$value]['versions']) && !empty($itemsData[$value]['versions'])) {
                            $inVersions = explode(", ", $itemsData[$value]['versions']);
                            foreach ($inVersions as $row) {
                                $versions[] = $row;
                            }

                            $objPrcZone = new PriceZonesDataSource();
                            $intPrcZoneId = $objPrcZone->getPriceZoneIdByVersions($versions);
                            $arrPriceId = $objPrcZone->checkPriceZoneExists($itemsData[$value]['master_items_id'], $eventId, $intPrcZoneId, $itemsData[$value]['id']);

                            if (!empty($arrPriceId)) {
                                foreach ($arrPriceId as $id) {
                                    if (in_array($id, $intPrcZoneId)) {
                                        $isExistsVer[$itemsData[$value]['id']] = $id;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        unset($versions);
        $existsItems = array_intersect($itemsId, array_keys($isExistsVer));
        $finalData = array_diff($itemsId, array_keys($isExistsVer));
        $arrResponse = ['existsItems' => $existsItems, 'availableItems' => array_flip($finalData)];

        return $arrResponse;
    }

    /**
     * Copy Items
     * @param object $command
     * @return array
     */        
//    function copyItemsold($command) {
//
//        $params = $command->dataToArray();
//        $status = false;
//        $objItems = new Items();
//        $objItems->dbTransaction();
//        try {
//            $commonData = [
//                'created_by' => isset($params['created_by']) ? $params['created_by'] : $params['last_modified_by'],
//                'last_modified_by' => $params['last_modified_by'],
//                'date_added' => $params['date_added'],
//                'last_modified' => $params['last_modified'],
//                'last_modified_by' => $params['last_modified_by'],
//                'gt_date_added' => $params['gt_date_added'],
//                'gt_last_modified' => $params['gt_last_modified'],
//                'ip_address' => $params['ip_address'],
//                'tracking_id' => $this->unique_id.'-0'
//            ];
//            $savedIds = $versionsCode = [];
//            $copyData = $this->prepareCopyItemsData($params);
//            
//            if (isset($copyData['copyData']) && !empty($copyData['copyData'])) {
//                foreach ($copyData['copyData'] as $row) {
//                    
//                    $objItemDs = new ItemsDataSource();
//                    $row['copy_items_id'] = $row['id'];
//                    $row['events_id'] = $params['to_events_id'];
//                    $row['id'] = "";
//                    unset($row['created_by']);
//                    $itemData = $row;
//                    $itemData = array_merge($commonData, $row);                    
//                    $saveID = $objItems->saveRecord($itemData);
//                                                                
//                    $itemsEditData = array_merge($commonData, $row);
//                    /**
//                     * find the versions and validation
//                     */
//                    $noVerfound = trim(strtolower(str_replace(' ', '', $itemData['versions'])));
//                    
////                    if(isset($itemData['versions']) && !empty($itemData['versions'])){
////                        if ($noVerfound != 'nopricezoneavailable.' && $noVerfound != 'nopricezonefound.') {
////                            $objPrcZoneDs = new PriceZonesDataSource();
////                            //$itemData['versions'] = GuzzleHttp\json_decode($itemData['versions']);
////                            $itemData['versions'] = explode(", ", $itemData['versions']);
////                            $versionsCode = $objPrcZoneDs->saveManualVersions(array('item_id' => $saveID->id, 'events_id' => $params['to_events_id'], 'versions' => $itemData['versions'], 'type' => 2, 'source' => 'copy'));
////                            
////                        }else{
////                            $versionsCode['versions'] = [$itemData['versions']];
////                        }
////                    }
//                    
//                    $price_versions = !empty($itemData['versions'] ) ? explode(", ", $itemData['versions']) : [];
//                    $omit_versions = !empty($itemData['mixed_column2']) ? explode(", ", $itemData['mixed_column2']) : [];
//                    $objPrcZoneDs = new PriceZonesDataSource();
//                    $versionsCode = $objPrcZoneDs->saveManualVersions(array('item_id' => $saveID->id, 'events_id' => $params['to_events_id'], 'versions' => $price_versions, 'omited_versions' => $omit_versions, 'type' => 2, 'source' => 'copy'));                                                            
//                    $itemsEditData['versions'] = isset($versionsCode['versions']) && !empty($versionsCode['versions']) ? implode(", ", $versionsCode['versions']) : 'No Price Zone found.';                                        
//                    $itemsEditData['mixed_column2'] = isset($versionsCode['omitVersions']) && !empty($versionsCode['omitVersions']) ? implode(", ", $versionsCode['omitVersions']) : '';                                        
//                    $itemsEditData['items_id'] = $saveID->id;                     
//                    $objItemDs->saveItemsEditData($itemsEditData);
//
//                    $itemsNonEdit = array_merge($commonData, $row);
//                    $itemsNonEdit['items_id'] = $saveID->id;
//                    $objItemDs->saveItemsNonEditData($itemsNonEdit);
//                    $savedIds[] = $saveID->id;
//                }
//                $command->save_ids = $savedIds;
//                $objLinkItems = new LinkedItemsDataSource();
//                $objLinkItems->copyLinkedItems($command);
//                $status = true;
//                $objItemDs->updateEventStatus($params['to_events_id']);
//                 /**
//                 * Copy Items Channels Adtypes
//                 */
//                $objChannelsDs = new ChannelsDataSource();
//                $objChannelsDs->copyItemsChannelsAdtypes($copyData['copyData'], $params['to_events_id']);
//                
//                /**
//                 * Track the activity logs
//                 */
//                $objLogs = new ActivityLog();
//                $logsData = $objLogs->setActivityLog(array('events_id' => $params['to_events_id'], 'actions' => 'copy', 'users_id' => $params['last_modified_by'], 'count' => count($savedIds), 'type' => '0', 'tracking_id' => $this->unique_id));
//                $objLogs->updateActivityLog($logsData);
//                unset($logsData);
////                $logsData = array_merge($commonData, ['events_id' => $params['to_events_id'], 'actions' => 'copy', 'users_id' => $params['last_modified_by'], 'descriptions' => count($savedIds) . ' Items Copied']);
////                unset($logsData['id']);
////                event(new ItemsActivityLogs($logsData));
//               
//            }
//            $objItems->dbCommit();
//        } catch (\Exception $ex) {
//            echo $ex->getMessage().$ex->getFile().$ex->getLine();
//            $objItems->dbRollback();
//            $status = false;
//        }
//        if (isset($copyData['copyData'])) {
//            unset($copyData['copyData']);
//        }
//        $response = array_merge(['status' => $status, 'items_id' => $savedIds], $copyData);
//        return $response;
//    }
    
    function copyItems($command) {

        $params = $command->dataToArray();
        $status = false;
        \DB::beginTransaction();
        try {
            if (isset($params['items_id']) && !empty($params['items_id'])) {
                $commonData = [
                                'created_by' => isset($params['created_by']) ? $params['created_by'] : $params['last_modified_by'],
                                'last_modified_by' => $params['last_modified_by'],
                                'date_added' => $params['date_added'],
                                'last_modified' => $params['last_modified'],
                                'last_modified_by' => $params['last_modified_by'],
                                'gt_date_added' => $params['gt_date_added'],
                                'gt_last_modified' => $params['gt_last_modified'],
                                'ip_address' => $params['ip_address'],
                                'tracking_id' => $this->unique_id . '-0'
                               ];
                $savedIds = $versionsCode = [];
                $copyData = $this->prepareCopyItemsData($params);

                $objEventsDs = new \CodePi\Events\DataSource\EventsDataSource;
                $historicalDates = $objEventsDs->getHistoricalReferenceDate($params['to_events_id']);
                $aprimoDetails = $objEventsDs->getAprimoDetails($params['to_events_id']);

                if (isset($copyData['copyData']) && !empty($copyData['copyData'])) {
                    foreach ($copyData['copyData'] as $row) {
                        $row['copy_items_id'] = $row['id'];
                        $row['events_id'] = $params['to_events_id'];
                        $row['id'] = '';
                        unset($row['created_by']);
                        $row = array_merge($commonData, $row);
                        $objItems = new Items();
                        $saveID = $objItems->saveRecord($row);
                        $row['items_id'] = $saveID->id;
                        /**
                         * Versions
                         */
                        $price_versions = !empty($row['versions']) ? explode(", ", $row['versions']) : [];
                        $omit_versions = !empty($row['mixed_column2']) ? explode(", ", $row['mixed_column2']) : [];
                        $objPrcZoneDs = new PriceZonesDataSource();
                        $versionsCode = $objPrcZoneDs->saveManualVersions(array('item_id' => $saveID->id, 'events_id' => $params['to_events_id'], 'versions' => $price_versions, 'omited_versions' => $omit_versions, 'type' => 2, 'source' => 'copy'));
                        $row['versions'] = isset($versionsCode['versions']) && !empty($versionsCode['versions']) ? implode(", ", $versionsCode['versions']) : 'No Price Zone found.';
                        $row['mixed_column2'] = isset($versionsCode['omitVersions']) && !empty($versionsCode['omitVersions']) ? implode(", ", $versionsCode['omitVersions']) : '';
                        $row['event_dates'] = $historicalDates;
                        $row['aprimo_campaign_id'] = isset($aprimoDetails['aprimo_campaign_id']) ? $aprimoDetails['aprimo_campaign_id'] : '';
                        $row['aprimo_campaign_name'] = isset($aprimoDetails['aprimo_campaign_name']) ? $aprimoDetails['aprimo_campaign_name'] : '';
                        $row['aprimo_project_id'] = isset($aprimoDetails['aprimo_project_id']) ? $aprimoDetails['aprimo_project_id'] : '';
                        $row['aprimo_project_name'] = isset($aprimoDetails['aprimo_project_name']) ? $aprimoDetails['aprimo_project_name'] : '';
                        $objEdit = new ItemsEditable();
                        $objEdit->saveRecord($row);
                        $objNonEdit = new ItemsNonEditable();
                        $objNonEdit->saveRecord($row);
                        $savedIds[] = $saveID->id;
                    }
                    $command->save_ids = $savedIds;
                    $objLinkItems = new LinkedItemsDataSource();
                    $objLinkItems->copyLinkedItems($command);
                    $status = true;
                    $objItemDs = new ItemsDataSource();
                    $objItemDs->updateEventStatus($params['to_events_id']);
                    /**
                     * Copy Items Channels Adtypes
                     */
                    $objChannelsDs = new ChannelsDataSource();
                    $objChannelsDs->copyItemsChannelsAdtypes($copyData['copyData'], $params['to_events_id']);

                    /**
                     * Track the activity logs
                     */
                    $objLogs = new ActivityLog();
                    $logsData = $objLogs->setActivityLog(array('events_id' => $params['to_events_id'], 'actions' => 'copy', 'users_id' => $params['last_modified_by'], 'count' => count($savedIds), 'type' => '0', 'tracking_id' => $this->unique_id));
                    $objLogs->updateActivityLog($logsData);
                    unset($logsData);
                }
            }
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
            $status = false;
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();            
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        
        if (isset($copyData['copyData'])) {
            unset($copyData['copyData']);
        }
        
        $response = array_merge(['status' => $status, 'items_id' => $savedIds], $copyData);
        
        return $response;
    }
    
    /**
     * Duplicate the selected items
     * @param object $command
     * @return array
     */
//    function duplicateItems($command) {
//        $duplicateIds = $selectedIds = [];
//        $status = false;
//        \DB::beginTransaction();
//        try {
//            $params = $command->dataToArray();
//            $rowData = $this->getItemListById(['items_id' => $params['item_id'], 'event_id' => $params['events_id']]);
//            $objItemDs = new ItemsDataSource();
//            $itemsData = $objItemDs->doArray($rowData);
//
//            if (!empty($itemsData)) {
//                unset($params['id'], $params['item_id']);
//                foreach ($itemsData as $data) {
//                    $original_id = $data['id'];
//                    $data['tracking_id'] = $this->unique_id . '-0';
//                    unset($data['id']);
//                    $objItems = new Items();
//                    $data = array_merge($data, $params);
//                    $items = $objItems->saveRecord($data);
//                    $data['items_id'] = $items->id;
//                    $data['versions'] = 'No Price Zone found.';
//                    $data['mixed_column2'] = '';
//                    $objEdit = new ItemsEditable();
//                    $objEdit->saveRecord($data);
//
//                    $objNonEdit = new ItemsNonEditable();
//                    $objNonEdit->saveRecord($data);
//                    $duplicateIds[$original_id] = $items->id;
//                    $selectedIds[] = $original_id;
//                }
//
//                $objItems->whereIn('id', $selectedIds)->update(['last_modified' => PiLib::piDate()]);
//                /**
//                 * Duplicate the Channels Adtypes
//                 */
//                $this->duplicateChannelsAdTypes($duplicateIds, $params['events_id']);
//                /**
//                 * Duplicate child items inside the parent items
//                 */
//                $objGroupDs = new GroupedDataSource();
//                if (isset($params['parent_item_id']) && !empty($params['parent_item_id'])) {
//                    $objGroupDs->duplicateGroupedItems(array('duplicate_item' => $duplicateIds));
//                }
//                /**
//                 * Duplicate grouped paranet item as a child items to exists group
//                 */
//                $objGroupDs->duplicateParentItem(array('duplicate_item' => $duplicateIds));
//                /**
//                 * If duplicated item is grouped items, removed from main items list, and to grouped item list
//                 * This method to get the newly grouped items id, this id will add into deleted items array
//                 */
//                $groupedId = $objGroupDs->getDuplicatedGroupedItems(array('duplicate_item' => $duplicateIds));
//                $isGroupedItems = isset($groupedId['group_item_id']) && !empty($groupedId['group_item_id']) && !empty($params['single_item']) && empty($params['parent_item_id']) ? true : false;
//                /**
//                 * Track the activity logs
//                 */
//                $objLogs = new ActivityLog();
//                $logsData = $objLogs->setActivityLog(array('tracking_id' => $this->unique_id, 'events_id' => $params['events_id'], 'actions' => 'duplicate', 'users_id' => $params['last_modified_by'], 'count' => count($duplicateIds), 'type' => '0', 'tracking_id' => $this->unique_id));
//                $objLogs->updateActivityLog($logsData);
//                unset($logsData);
//            }
//            $status = true;
//            \DB::commit();
//        } catch (\Exception $ex) {
//            \DB::rollback();
//            $status = false;
//            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
//            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
//        }
//
//        //$selectedIds = empty($params['single_item']) ? $selectedIds : [];
//        return ['status' => $status, 'items_id' => $duplicateIds,/*array_merge(array_values($duplicateIds), $selectedIds),*/ 'isDuplicateFromGroup' => $isGroupedItems];
//    }
    
    function duplicateItems($command) {
        $duplicateIds = $selectedIds = [];
        $status = false;
        \DB::beginTransaction();
        try {
            $params = $command->dataToArray();
            $rowData = $this->getItemListById(['items_id' => $params['item_id'], 'event_id' => $params['events_id']]);
            $objItemDs = new ItemsDataSource();
            $itemsData = $objItemDs->doArray($rowData);            
           
            if (!empty($itemsData)) {
                unset($params['id'], $params['item_id']);
                foreach ($itemsData as $data) {
                    $original_id = $data['id'];
                    $data['tracking_id'] = $this->unique_id . '-0';
                    unset($data['id']);
                    $objItems = new Items();
                    $data = array_merge($data, $params);
                    $items = $objItems->saveRecord($data);
                    $data['items_id'] = $items->id;                    
                    $objEdit = new ItemsEditable();
                    $objEdit->saveRecord($data);

                    $objNonEdit = new ItemsNonEditable();
                    $objNonEdit->saveRecord($data);
                    $duplicateIds[$original_id] = $items->id;
                    $selectedIds[] = $original_id;
                }
                
                $objItems->whereIn('id', $selectedIds)->update(['last_modified' => PiLib::piDate()]);
                /**
                 * Duplicate Priceversions
                 */
                $objPriceVersion = new PriceZonesDataSource();
                $objPriceVersion->duplicatePriceVersions($selectedIds, $duplicateIds, $params);
                /**
                 * Duplicate the Channels Adtypes
                 */
                $this->duplicateChannelsAdTypes($duplicateIds, $params['events_id']);
                /**
                 * Duplicate child items inside the parent items
                 */
                $objGroupDs = new GroupedDataSource();
                if (isset($params['parent_item_id']) && !empty($params['parent_item_id'])) {
                    $objGroupDs->duplicateGroupedItems(array('duplicate_item' => $duplicateIds));
                }
                /**
                 * Duplicate grouped paranet item as a child items to exists group
                 */
                $objGroupDs->duplicateParentItem(array('duplicate_item' => $duplicateIds));
                /**
                 * If duplicated item is grouped items, removed from main items list, and to grouped item list
                 * This method to get the newly grouped items id, this id will add into deleted items array
                 */
                $groupedId = $objGroupDs->getDuplicatedGroupedItems(array('duplicate_item' => $duplicateIds));
                $isGroupedItems = isset($groupedId['group_item_id']) && !empty($groupedId['group_item_id']) && !empty($params['single_item']) && empty($params['parent_item_id']) ? true : false;
                /**
                 * Track the activity logs
                 */
                $objLogs = new ActivityLog();
                $logsData = $objLogs->setActivityLog(array('tracking_id' => $this->unique_id, 'events_id' => $params['events_id'], 'actions' => 'duplicate', 'users_id' => $params['last_modified_by'], 'count' => count($duplicateIds), 'type' => '0', 'tracking_id' => $this->unique_id));
                $objLogs->updateActivityLog($logsData);
                unset($logsData);
            }
            $status = true;
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
            $status = false;
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }

        //$selectedIds = empty($params['single_item']) ? $selectedIds : [];
        return ['status' => $status, 'items_id' => $duplicateIds,/*array_merge(array_values($duplicateIds), $selectedIds),*/ 'isDuplicateFromGroup' => $isGroupedItems];
    }

    /**
     * Duplicate the channels adtypes
     * @param array $itemsids
     * @return boolean
     */
    function duplicateChannelsAdTypes(array $arrItemIds) {
        $objItems = new Items();
        $objItemsAdtype = new ChannelsItems();
        DB::beginTransaction();
        $arrSaveData = $arrAdtypeData = [];
        try {
            if (!empty($arrItemIds)) {
                foreach ($arrItemIds as $orignalId => $duplicateId) {
                    $arrAdtypeData[$duplicateId] = $objItemsAdtype->where('items_id', $orignalId)->get()->toArray();
                }
                if (!empty($arrAdtypeData)) {
                    foreach ($arrAdtypeData as $newItemId => $adtypeValue) {
                        foreach ($adtypeValue as $values) {
                            $arrSaveData[] = ['items_id' => $newItemId, 'channels_id' => $values['channels_id'], 'channels_adtypes_id' => $values['channels_adtypes_id']];
                        }
                    }
                    $objItemsAdtype->insertMultiple($arrSaveData);
                }
            }
            unset($arrAdtypeData, $arrSaveData);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
        }
        return true;
    }

}
