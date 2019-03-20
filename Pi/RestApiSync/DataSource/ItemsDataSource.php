<?php

namespace CodePi\RestApiSync\DataSource;

use CodePi\Base\DataSource\DataSource;
#use CodePi\Base\Commands\BaseCommand;
use DB;
#use URL;
use CodePi\Base\DataSource\Elastic;
use CodePi\Base\Eloquent\ItemsHeaders;
use CodePi\Base\Eloquent\Items;
#use CodePi\Base\Eloquent\ItemsEditable;
#use CodePi\Base\Eloquent\ItemsNonEditable;
use CodePi\Base\Eloquent\History;
use CodePi\Base\Eloquent\Users;
use CodePi\Items\DataSource\ItemsDataSource as ItemDataSource;
use CodePi\RestApiSync\Utils\ImportElasticUtils;
use CodePi\Items\Utils\ItemsUtils;
use CodePi\RestApiSync\DataSource\DataSourceInterface\iImportElastic;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Eloquent\MasterDataOptions;
class ItemsDataSource implements iImportElastic {
    
    /**
     * Set Enum column array, need to be convert as a boolean, while store into elasticsearch
     * @var array
     */
    private $booleanArray = array('is_excluded', 
                                  'is_no_record', 
                                  'publish_status', 
                                  'items_type', 
                                  'item_sync_status'
                                 );
    private $actionType = array('insert', 'update', 'delete');


    /**
     * Get All Items Data from database, only marketing events related items will be imported into elasticsearch
     * @param type $command
     * @return collection
     */
    function getAllData($command) {

        $params = $command->dataToArray();
        $getColumns = $this->getItemDefaultHeaders($type = 0);
        $column = [];
        foreach ($getColumns as $key => $value) {
            $column[] = 'trim(' . $value['aliases_name'] . '.' . $key . ') as ' . $key;
        }
        $objItems = new Items();
        $columnName = implode($column, ',');
        unset($column);
        
        $objResult = $objItems->dbTable('i')
                              ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                              ->join('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                              ->join('events as e', 'e.id', '=', 'i.events_id')
                              ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                              ->leftJoin('departments as d', 'u.departments_id', '=', 'd.id')
                              ->select('i.id', 'i.items_type', 'i.date_added', 'i.last_modified', 'i.events_id', 'i.is_excluded', 'i.is_no_record', 'i.item_sync_status', 'i.publish_status', 'i.master_items_id', 'i.created_by', 'u.departments_id', 'i.cell_color_codes','u.email as users_id','d.name as departments', 'e.name as event_name', 'i.items_import_source')
                              ->selectRaw('(select count(link_item_parent_id) from items where link_item_parent_id = i.id) as link_count')
                              ->selectRaw($columnName)
                              ->selectRaw('(SELECT mp.name FROM master_data_options AS mp WHERE mp.id = SUBSTRING_INDEX(ie.local_sources, ":",-1)) as local_sources')
                              ->where('e.is_draft', '0')                
                              ->groupBy('i.id')
                              ->orderBy('i.id', 'asc')
                              ->offset($params['offset'])->limit(2500)
                              ->get();
        
        return $objResult;
    }
    
    /**
     * Get items columns
     * @param type $type
     * @return array
     */
    function getItemDefaultHeaders($type) {

        $arrResponse = [];
        $objItemsHeaders = new ItemsHeaders();
        if ($type) {
            $linkedCol = ['1'];
        } else {
            $linkedCol = ['0', '1'];
        }
        $objResult = $objItemsHeaders->where('status', TRUE)
                                     ->whereIn('is_linked_item', $linkedCol)
                                     ->orderBy('column_order', 'asc')
                                     ->get();

        foreach ($objResult as $column) {
                $arrResponse[$column->column_name] = ['id' => $column->id,
                                                      'column' => $column->column_name,
                                                      'name' => $column->column_label,
                                                      'type' => $column->field_type,
                                                      'aliases_name' => $column->table_aliases_name,
                                                      'column_source' => $column->column_source,
                                                     ];
        }

        return $arrResponse;
    }

    function getItemsChannels() {
        $sql = "SELECT 
                `ci`.`items_id`, 
                `c`.`id`, 
                `c`.`name`, 
                cat.name AS ad_types_name,
                cat.id AS ad_types_id
                FROM `channels_events` AS `ce`
                INNER JOIN `channels_items` AS `ci` ON `ci`.`channels_id` = `ce`.`channels_id`
                INNER JOIN `channels_ad_types` AS `cat` ON `cat`.`id` = `ci`.`channels_adtypes_id`
                INNER JOIN `channels` AS `c` ON `c`.`id` = `ce`.`channels_id` 
                GROUP BY items_id, ci.channels_id, ci.channels_adtypes_id
                ORDER BY c.id ASC";

        $result = \DB::select($sql);
        $channles = [];
        foreach ($result as $row) {
            $channles[$row->items_id][] = array('channel_id' => $row->id, 'channel_name' => $row->name, 'ad_types_name' => $row->ad_types_name, 'ad_types_id' => $row->ad_types_id);
        }

        return $channles;
    }
    /**
     * Get syncData from History table
     * @param type $params
     * @return collection
     */
    function getSyncData($params) {

        $objHistory = new History;
        $objHistory = $objHistory->where('is_es_sync', '1')
                                 ->where('action', $params['action'])
                                 ->whereIn('table_name', ['items', 'items_editable', 'items_non_editable'])
                                 ->orderBy('id', 'desc')
                                 ->limit(500)
                                 ->get();

        
        return $objHistory;
    }
    /**
     * Format the syndata result, to sycn into elastic search based on action
     * @param array $data
     * @return array
     */
    function prepareSynData($data) {
        $arrSynData = $arrData = [];
        
        if (!empty($data)) {

            foreach ($data as $row) {

                $tableValues = (array) json_decode($row->total_history);
                $updateFields = array_filter(explode(",", str_replace('"', '', str_replace(']', '', str_replace('[', '', $row->changed_fields)))));
                foreach ($tableValues as $key => $value) {
                    $arrData[$key] = $value[1];
                }

                $getItemUserDepartmentDetails = $this->getItemUserDepartmentDetails($row->users_id);
                if ($row->action == 'update') {
                    if (!empty($updateFields)) {
                        foreach ($updateFields as $fields) {

                            if (isset($arrData[$fields])) {
                                $arrSynData[$row->items_id][$fields] = $arrData[$fields];
                                $arrSynData[$row->items_id]['id'] = $row->items_id;
                                $arrSynData[$row->items_id]['created_by'] = isset($row->users_id) ? $row->users_id : 0;
                                $arrSynData[$row->items_id]['users_id'] = isset($getItemUserDepartmentDetails[0]->email) ? $getItemUserDepartmentDetails[0]->email : '';
                                $arrSynData[$row->items_id]['departments_id'] = isset($getItemUserDepartmentDetails[0]->departments_id) ? $getItemUserDepartmentDetails[0]->departments_id : 0;
                                $arrSynData[$row->items_id]['departments'] = isset($getItemUserDepartmentDetails[0]->name) ? $getItemUserDepartmentDetails[0]->name : '';
                                if (in_array($fields, $this->booleanArray)) {
                                    $arrSynData[$row->items_id][$fields] = ($arrData[$fields] == '1') ? true : false;
                                }

                                if ($fields == 'attributes') {
                                    $this->itemDataSource = new ItemDataSource();
                                    $arrSynData[$row->items_id][$fields] = $this->itemDataSource->getAttributesSelectedValues($arrData['attributes']);
                                }
                                if ($fields == 'local_sources') {                                                   
                                    $arrSynData[$row->items_id][$fields] = $this->getVendorSupplyValue($arrData['local_sources']);
                                }
                            }
                        }
                    }
                    
                } else if ($row->action == 'insert') {
                    
                    $arrSynData[$row->items_id] = $arrData;
                    $arrSynData[$row->items_id]['id'] = $row->items_id;
                    $arrSynData[$row->items_id]['created_by'] = isset($row->users_id) ? $row->users_id : 0;
                    $arrSynData[$row->items_id]['users_id'] = isset($getItemUserDepartmentDetails[0]->email) ? $getItemUserDepartmentDetails[0]->email : '';
                    $arrSynData[$row->items_id]['departments_id'] = isset($getItemUserDepartmentDetails[0]->departments_id) ? $getItemUserDepartmentDetails[0]->departments_id : 0;
                    $arrSynData[$row->items_id]['departments'] = isset($getItemUserDepartmentDetails[0]->name) ? $getItemUserDepartmentDetails[0]->name : '';
                    $arrSynData[$row->items_id]['is_excluded'] = isset($arrData['is_excluded']) && ($arrData['is_excluded'] == '1') ? true : false;
                    $arrSynData[$row->items_id]['is_no_record'] = isset($arrData['is_no_record']) && ($arrData['is_no_record'] == '1') ? true : false;
                    $arrSynData[$row->items_id]['publish_status'] = isset($arrData['publish_status']) && ($arrData['publish_status'] == '1') ? true : false;
                    $arrSynData[$row->items_id]['items_type'] = isset($arrData['items_type']) && ($arrData['items_type'] == '1') ? true : false;
                    $arrSynData[$row->items_id]['item_sync_status'] = isset($arrData['item_sync_status']) && ($arrData['item_sync_status'] == '1') ? true : false;
                    if (isset($arrData['attributes']) && !empty($arrData['attributes'])) {
                        $this->itemDataSource = new ItemDataSource();
                        $arrSynData[$row->items_id]['attributes'] = $this->itemDataSource->getAttributesSelectedValues($arrData['attributes']);
                    } else {
                        $arrSynData[$row->items_id]['attributes'] = NULL;
                    }
                    if (isset($arrData['local_sources']) && !empty($arrData['local_sources'])) {
                        $arrSynData[$row->items_id]['local_sources'] = $this->getVendorSupplyValue($arrData['local_sources']);
                    }

                    $intEventId = isset($arrData['events_id']) ?$arrData['events_id'] : '';
                    $upcNbr = isset($arrData['upc_nbr']) ?$arrData['upc_nbr'] : '';
                    $arrSynData[$row->items_id]['no_of_linked_item'] = $this->getLinkedItemsCount($intEventId, $upcNbr);
                    
                } else if ($row->action == 'delete') {
                    $arrSynData[$row->items_id]['id'] = $row->items_id;
                }
            }
        }
        
        return array_values($arrSynData);
    }

    /**
     * 
     * @return Array
     */
//    function booleanCoumnsArray(){
//        return ['is_excluded', 'is_no_record', 'publish_status', 'items_type', 'item_sync_status'];
//    }

    /**
     * Update the ElasticSearch Syns status After sync data into Elastic search
     * @param int $intID
     * @param String $action
     * @param String $table
     * @param Enum $isEsSyncStatus
     * @return boolean
     */
    function updateIsEsSyncFlag($intID, $action, $table = null, $isEsSyncStatus) {
        DB::beginTransaction();

        try {
            $objHistory = new History;
            if ($table == 'events') {

                $objHistory = $objHistory->whereIn('events_id', $intID);
            } else {
                $objHistory = $objHistory->whereIn('items_id', $intID);
            }
            $objHistory = $objHistory->where('action', $action)
                            ->where(function ($query) use ($table) {
                                if (!empty($table)) {
                                    $query->where('table_name', $table);
                                }
                            })->update(['is_es_sync' => $isEsSyncStatus]);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
        }

        return true;
    }

    /**
     * Get Items Channels Adtypes History data
     * @param String $action
     * @params action > Insert & Delete
     * @return array
     */
//    function getItemsChannelsSyncData($action) {
//        $sql = "SELECT * FROM history AS h WHERE h.table_name = 'channels_items' AND is_es_sync = '1' AND h.action = '" . $action . "' ORDER BY id ASC";
//        $result = DB::select($sql);
//        $arrChannels = [];
//
//        foreach ($result as $row) {
//            $isValid = $this->jsonValidator((array) json_decode($row->total_history));
//            if ($isValid) {
//                $tableValues = (array) json_decode($row->total_history);
//                $arrChannels[$row->items_id]['channels'][$tableValues['channels_adtypes_id'][0]] = $this->getChannelsInfo($tableValues['channels_id'][0], $tableValues['channels_adtypes_id'][0]);
//            }
//        }
//        return $arrChannels;
//    }
//
//    /**
//     * 
//     * @param type $intItemsId
//     * @return array
//     */
//    function getAssignedChannelsAdtypes($intItemsId) {
//        $sql = "SELECT 
//                c.name AS channel_name, c.id AS channel_id, cat.id AS ad_types_id, cat.name AS ad_types_name
//                FROM channels_items AS ci
//                LEFT JOIN channels AS c ON c.id = ci.channels_id
//                LEFT JOIN channels_ad_types AS cat ON cat.id = ci.channels_adtypes_id
//                WHERE ci.items_id = " . $intItemsId . " ORDER BY ci.id ASC";
//        $objChannelsItems = new \CodePi\Base\Eloquent\ChannelsItems();
//        $result = $objChannelsItems->dbSelect($sql);
//        $data = [];
//        if (!empty($result)) {
//            foreach ($result as $row) {
//                $data[$intItemsId]['channels'][$row->ad_types_id] = ['channel_id' => $row->channel_id, 'channel_name' => $row->channel_name, 'ad_types_name' => $row->ad_types_name, 'ad_types_id' => $row->ad_types_id];
//            }
//        } else {
//            $data[$intItemsId]['channels'] = array();
//        }
//
//        return $data;
//    }

    /**
     * 
     * @param type $data
     * @return boolean
     */
//    function jsonValidator($data = NULL) {
//        if (!empty($data)) {
//            @json_decode($data);
//            return (json_last_error() === JSON_ERROR_NONE);
//        }
//        return false;
//    }
//
//    /**
//     * 
//     * @param type $intChannelsId 
//     * @param type $intAdtypesId
//     * @return type
//     */
//    function getChannelsInfo($intChannelsId = 0, $intAdtypesId) {
//        $data = [];
//        $sql = " SELECT c.id, c.name as channels_name, cat.id as ad_types_id, cat.name as ad_types_name "
//                . "FROM channels AS c "
//                . "LEFT JOIN channels_ad_types AS cat ON cat.channels_id = c.id "
//                . "WHERE c.id = " . $intChannelsId . " AND cat.id = " . $intAdtypesId . " ";
//        $result = DB::select($sql);
//        if (!empty($result)) {
//            foreach ($result as $row) {
//                $data = ['channel_id' => $row->id, 'channel_name' => $row->channels_name, 'ad_types_name' => $row->ad_types_name, 'ad_types_id' => $row->ad_types_id];
//            }
//        }
//        return $data;
//    }

    function getItemsTotalCount() {
        $sql = "SELECT COUNT(*) AS itemCnt FROM items AS i INNER JOIN events AS e ON e.id = i.events_id  WHERE e.is_draft = '0' LIMIT 1";
        $result = DB::select($sql);
        $totalCount = 0;
        if (!empty($result)) {
            $totalCount = isset($result[0]) && isset($result[0]->itemCnt) ? $result[0]->itemCnt : 0;
        }
        return $totalCount;
    }

    /**
     * Clear the entire data in ElasticSearch
     * @param String $index = The name of index in ElasticSearch
     * @param String $type = The name of type in ElasticSearch
     * @return type     
     */
//    function clearDataInEsearch($index, $type) {
//        $objE = new Elastic();
//        $params['index'] = $index;
//        $params['type'] = $type;
//        $params['body'] = array('query' => array('range' => array('id' => array('gte' => 1))));
//        return $objE->deleteByQuery($params);
//    }

    /**
     * Check the the ID exists in ElasticSearch
     * @param String $index 
     * @param String $type
     * @param Int $intId
     * @return Count
     */
//    function checkIdExistsInEls($index, $type, $intId) {
//        $objE = new Elastic();
//        $params['index'] = $index;
//        $params['type'] = $type;
//        $params['body'] = array('query' => array('match' => array('id' => $intId)));
//        $result = $objE->search($params);
//        $total = isset($result['hits']) && isset($result['hits']['total']) ? $result['hits']['total'] : 0;
//        return $total;
//    }

//    function makeEmptyValueInEs($params) {
//        $objElastic = new Elastic();
//        $objE = [];
//        $objE['index'] = 'sm_items';
//        $objE['type'] = 'items';
//        $objE['id'] = $params['id'];
//        $objE['body'] = ['doc' => $params];
//        $objElastic->update($objE);
//    }
    
    function getItemUserDepartmentDetails($id) {
        $objUsers = new Users();
        $result = $objUsers->dbTable('u')
                        ->select('u.email', 'u.departments_id', 'd.name')
                        ->join('departments as d', 'd.id', '=', 'u.departments_id')
                        ->where('u.id', $id)->get();
        return $result;
    }
    
    function getLinkedItemsCount($intEventId, $upcNbr){
        $objItems = new Items();
        $result = 0;
        if(!empty($upcNbr)){
            $result = $objItems->where('events_id', $intEventId)
                               ->where('upc_nbr', $upcNbr)
                               ->where('items_type', '1')
                               ->orderBy('id', 'asc')->limit(1)->count();
        }
        return $result;
        
    }
    /**
     * 
     * @param type $intId
     * @return type
     */
    function getSelectedVersions($intId) {
        $versions = 'No Price Zone found.';
        $objItemPriceZone = new \CodePi\Base\Eloquent\ItemsPriceZones();
        $sql = "SELECT GROUP_CONCAT(pz.versions ORDER BY pz.versions ASC SEPARATOR ', ') AS versions
               FROM items_price_zones AS ipz
               INNER JOIN price_zones AS pz ON pz.id = ipz.price_zones_id
               WHERE ipz.items_id= " . $intId . " AND ipz.is_omit='0'
               LIMIT 1";
        $result = $objItemPriceZone->dbSelect($sql);
        if (isset($result[0]) && isset($result[0]->versions)) {
            $versions = $result[0]->versions;
        }
        return $versions;
    }
    /**
     * 
     * @param type $intItemId
     * @return type
     */
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
    /**
     * Import data into elasticsearch
     * @param array $command
     * @return array
     */
    function importDataToEs($command) {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $status = false;
        $exMsg = '';        
        try {
            $channels = $this->getItemsChannels();
            $objUtils = new ImportElasticUtils();
            $objUtils->setIndex('sm_items');
            $objUtils->setType('items');
            $objUtils->setTableName(array('items', 'items_editable', 'items_non_editable'));
            $objUtils->setSyncStatusInHistory();
            $objUtils->deleteAll();
            
            $toatlCount = $this->getItemsTotalCount();            
            $index = $objUtils->getIndex();
            $type = $objUtils->getType();
            
            for ($i = 0; $i <= $toatlCount; $i = $i + 2500) {
                if ($i) {
                    $command->offset = $i + 1;
                } else {
                    $command->offset = $i;
                }
                $result = $this->getAllData($command);
                $itemsData = $this->formatItemsDataToImport($result, $index, $type, $channels);
                $objElastic = new Elastic();
                $objElastic->bulk($itemsData);
            }
            $status = true;
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            //return CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return array('status' => $status, 'msg' => $exMsg);
    }
    /**
     * Prepare data to import into the elasticsearch index
     * @param object $result
     * @param string $index
     * @param string $type
     * @param array $channels
     * @return array
     */
    function formatItemsDataToImport($result, $index, $type, $channels) {
        $itemData = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $objItemsDs = new ItemDataSource();
                $isEmpty = ItemsUtils::isRowEmpty($row);
                if ($isEmpty == false) {
                    $row->is_excluded = !empty($row->is_excluded) ? true : false;
                    $row->is_no_record = !empty($row->is_no_record) ? true : false;
                    $row->item_sync_status = !empty($row->item_sync_status) ? true : false;
                    $row->publish_status = !empty($row->publish_status) ? true : false;
                    $row->is_excluded = !empty($row->is_excluded) ? true : false;
                    $row->items_type = !empty($row->items_type) ? true : false;
                    $row->items_import_source = ($row->items_import_source == '1') ? 'Import' : 'IQS';
                    $row->date_added = $row->date_added;
                    $row->last_modified = $row->last_modified;
                    $row->channels = isset($channels[$row->id]) ? $channels[$row->id] : [];
                    $row->attributes = $objItemsDs->getAttributesSelectedValues($row->attributes);                    
                    $row->local_sources = !empty($row->local_sources) && ($row->local_sources != 'Yes') ? 'No - '.$row->local_sources : 'Yes';
                    //$row->versions = $this->getSelectedVersions($row->id);                    
                    $itemData['body'][] = [ 'index' =>[
                                                        '_index' => $index,
                                                        '_type' => $type,
                                                        '_id' => $row->id
                                                       ]
                                          ];
                    $itemData['body'][] = $row;
                }
            }
            unset($result);
        }
        return $itemData;
    }
    
    /**
     * 
     * @param type $command
     * @return boolean
     */
    function syncDataToElastic($command) {
        $status = false;
        try {
            $params = $command->dataToArray();
            $action = $this->actionType;

            foreach ($action as $value) {
                
                $params['action'] = $value;
                $data = $this->getSyncData($params);                
                $result = $this->prepareSynData($data);
                
                if ($value == 'insert') {
                   $this->insertBulkRecord($result);
                } else if ($value == 'update') {                    
                   $this->updateBulkRecord($result);
                } else if ($value == 'delete') {
                   $this->deleteBulkRecord($result);
                }
            }
            $status = true;
        } catch (\Exception $ex) {
            $exMsg = $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();
            //CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $status;
    }
    /**
     * Bulk insert to elasticsearch
     * @param type $data
     */
    function insertBulkRecord($data) {
        $itemData = $success_id = $failure_id = [];        
        try{
            if(!empty($data)){
                foreach ($data as $row) {

                    $objUtils = new ImportElasticUtils();
                    $objUtils->setIndex('sm_items');
                    $objUtils->setType('items');
                    $isExists = $objUtils->isExistsInIndex($row['id']);

                    if (empty($isExists)) {
                        $row['last_modified'] = !empty($m['last_modified']) ? $row['last_modified'] : \CodePi\Base\Libraries\PiLib::piDate();
                        $objUtils->setAction('index');
                        $itemData['body'][] = $objUtils->getIndexBody($row['id']);
                        $itemData['body'][] = $row;
                        $success_id[] = $row['id'];
                    }else{
                        $failure_id[] = $row['id'];
                    }
                }
                if(!empty($itemData)){
                    $objElastic = new Elastic();
                    $objElastic->bulk($itemData);
                    unset($itemData);                                
                }
                if(!empty($success_id)){
                        $this->updateIsEsSyncFlag($success_id, $action = 'insert', null, '0');
                }
                if(!empty($failure_id)){
                    $this->updateIsEsSyncFlag($failure_id, $action = 'insert', null, '2');
                }
            }
        } catch (\Exception $ex) {
            $exMsg = $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();
            //CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        
        
    }
    /**
     * Bulk update to elasticsearch
     * @param type $data
     */
    function updateBulkRecord($data) {
        $itemData = $success_id = $failure_id = [];        
        try {
            if (!empty($data)) {
                foreach ($data as $row) {
                    
                    $objUtils = new ImportElasticUtils();
                    $objUtils->setIndex('sm_items');
                    $objUtils->setType('items');
                    $isExists = $objUtils->isExistsInIndex($row['id']);
                    if ($isExists) {
                        $row['last_modified'] = !empty($row['last_modified']) ? $row['last_modified'] : PiLib::piDate();                        
                        $objUtils->setAction('update');
                        $itemData['body'][] = $objUtils->getIndexBody($row['id']);
                        $itemData['body'][] = ['doc' => $row];
                        $success_id[] = $row['id'];
                    } else {
                        $failure_id[] = $row['id'];
                    }
                }
                
                if (!empty($itemData)) {
                    $objElastic = new Elastic();
                    $objElastic->bulk($itemData);
                    unset($itemData);
                }
                if (!empty($success_id)) {
                    $this->updateIsEsSyncFlag($success_id, $action = 'update', null, '0');
                }
                if (!empty($failure_id)) {
                    $this->updateIsEsSyncFlag($failure_id, $action = 'update', null, '2');
                }
            }
        } catch (\Exception $ex) {
            $exMsg = $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();
            //CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
    }

    /**
     * Bulk delete to elasticsearch
     * @param type $data
     */
    function deleteBulkRecord($data) {
        
        $itemData = $success_id = $failure_id = [];   
        try{
            if(!empty($data)){
                foreach ($data as $row) {

                    $objUtils = new ImportElasticUtils();
                    $objUtils->setIndex('sm_items');
                    $objUtils->setType('items');
                    $isExists = $objUtils->isExistsInIndex($row['id']);

                    if ($isExists) {
                        $row['last_modified'] = !empty($row['last_modified']) ? $row['last_modified'] : PiLib::piDate();
                        $objUtils->setAction('delete');
                        $itemData['body'][] = $objUtils->getIndexBody($row['id']);
                        $success_id[] = $row['id'];
                    }else{
                        $failure_id[] = $row['id'];
                    }
                }

                if(!empty($itemData)){                
                    $objElastic = new Elastic();
                    $objElastic->bulk($itemData);
                    unset($itemData);                
                }

                if(!empty($success_id)){
                    $this->updateIsEsSyncFlag($success_id, $action = 'delete', null, '0');
                }
                if(!empty($failure_id)){
                    $this->updateIsEsSyncFlag($failure_id, $action = 'delete', null, '2');
                }
            } 
        } catch (\Exception $ex) {
            $exMsg = $ex->getMessage() . ' :: ' . $ex->getFile() . ' :: ' . $ex->getLine();
            //CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        
    }
    /**
     * 
     * @param type $value
     * @return string
     */
    function getVendorSupplyValue($value) {
        $localSource = 'Yes';        
        if ($value) {
            $objMasterData = new MasterDataOptions();
            $objColl = $objMasterData->dbTable("m")->whereRaw("m.id = SUBSTRING_INDEX('" . $value . "', ':', -1)")->limit(1)->get();
            if (isset($objColl[0]) && !empty($objColl[0])) {
                $localSource = 'No - ' . $objColl[0]->name;
            }
        }
        return $localSource;
    }

}
