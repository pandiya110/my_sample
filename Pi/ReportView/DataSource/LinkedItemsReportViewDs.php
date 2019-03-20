<?php

namespace CodePi\Items\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Items;
use CodePi\Base\Eloquent\Events;
use GuzzleHttp;
use CodePi\Base\Eloquent\ItemsHeaders;
use CodePi\Base\Eloquent\ItemsEditable;
use CodePi\Base\Eloquent\ItemsGroupsItems;
use CodePi\Base\Eloquent\ItemsNonEditable;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Libraries\PiLib;
use CodePi\Items\DataSource\ItemsDataSource;
use App\Events\UpdateEventStatus;
#use CodePi\Items\Commands\GetItemsList;
#use CodePi\ItemsActivityLog\DataSource\ItemsActivityLogsDs;
use App\Events\ItemsActivityLogs;
#use CodePi\Api\Commands\GetMasterItems;
#use CodePi\Api\DataSource\EmiApiDataSource;
use CodePi\Base\Eloquent\MasterItems;
use CodePi\Base\Eloquent\Users;
use CodePi\SyncItems\DataSource\SyncDataSource;
use CodePi\Items\DataSource\GroupedDataSource;

/**
 * Handle the linked items save ,listing and search
 */
class LinkedItemsReportViewDs {

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
 function getReportViewData($command) {

        $params = $command->dataToArray();
        
        try {
            $objItemsDs = new ItemsDataSource();            
            $users_id = (isset($params['users_id']) && $params['users_id'] != 0) ? $params['users_id'] : $params['last_modified_by'];
            $objUsers = new Users();
            $userData = $objUsers->where('id', $users_id)->first();
            $departments_id = !empty($userData) ?$userData->departments_id : 0;           
            $permissions = $objItemsDs->getAccessPermissions($users_id);
            $permissions['departments_id'] = $departments_id;
            $itemType = isset($params['item_type']) ? $params['item_type'] : '1';
            $headerType = ($params['item_type'] == '0') ? 0 : 2;
            
            /*
             * Get the default item headers by order 
             */
           $getColumns = $this->getLinkedItmColumn();

        $column = $searchColumn = [];
        foreach ($getColumns as $key => $value) {
            $column[] = 'trim(' . $value['aliases_name'] . '.' . $key . ') as ' . $key;
            $searchColumn[] = $value['aliases_name'] . '.' . $key;
        }
        $columnName = implode($column, ',');

            unset($column);
            $isAnd = true;
            $objItems = new Items();
            
            $objResult = $objItems->dbTable('i')
                                  ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                                  ->join('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                                  ->join('events as e', 'e.id', '=', 'i.events_id')                
                                  ->leftJoin('users as u', 'u.id', '=', 'i.created_by')                    
                                  ->select('i.id', 'i.is_excluded', 'i.is_no_record', 'i.item_sync_status', 'i.publish_status', 
                                           'i.master_items_id', 'i.created_by', 
                                           'u.departments_id', 'i.cell_color_codes', 'e.name as event_name', 'i.events_id','i.items_import_source')
                                   ->selectRaw($columnName)
                                   ->where(function ($query) use ($params) {
                                      if (isset($params['itemsListUserId']) && !empty($params['itemsListUserId'])) {
                                            $query->where('i.created_by', $params['itemsListUserId']);
                                       }
                                   })->where(function ($query) use ($params) {
                                       if (isset($params['department_id']) && !empty($params['department_id'])) {
                                            $query->where('u.departments_id', $params['department_id']);
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
                                        if (isset($params['is_no_record']) && !empty($params['is_no_record'])) {
                                            $query->where('i.is_no_record', $params['is_no_record']);
                                        }
                                   })->where(function ($query) use ($params) {
                                        if (isset($params['item_sync_status']) && !empty($params['item_sync_status'])) {
                                            $query->where('i.item_sync_status', $params['item_sync_status']);
                                        }
                                   })->where('i.items_type', $itemType)
                                     ->where(function($query) use ($permissions, $params) {
                                        if (isset($permissions['items_access']) && ($permissions['items_access'] == '6' || $permissions['items_access'] == '4' || $permissions['items_access'] == '5')) {

                                        } else if (isset($permissions['items_access']) && ($permissions['items_access'] == '2' || $permissions['items_access'] == '3')) {
                                            $query->where('u.departments_id', $permissions['departments_id']);
                                        } else {
                                            $query->where('i.created_by', $params['last_modified_by']);
                                        }
                                   });
            /**
             * Sorting
             */
            if (isset($params['multi_sort']) && !empty($params['multi_sort'])) {
                $multipleOrderBy = $objItemsDs->addMultiSortConditions($params['multi_sort']);
                $objResult = $objResult->orderByRaw(!empty($multipleOrderBy) ? $multipleOrderBy : 'i.last_modified desc');
            } else if (isset($params['order']) && !empty($params['order']) && (isset($params['sort']) && !empty($params['sort']))) {
                $activityColumn = ['is_no_record', 'is_excluded', 'item_sync_status'];
                $and = "";
                $aliase = $objItemsDs->findTableAliaseName($params['order']);
                $data_type = (isset($aliase['type']) && $aliase['type'] == 'numeric') ? 'unsigned' : 'char';
                $sort = in_array($params['order'], $activityColumn) ? 'desc' : $params['sort'];
                if ($params['order'] == 'no_of_linked_item') {
                    $objResult = $objResult->orderBy('link_count', $sort);
                } else {

                    $objResult = $objResult->orderByRaw('cast(' . $aliase['aliase'] . '.' . $params['order'] . ' as ' . $data_type . ') ' . $sort . '');
                }
            } else {
                $objResult = $objResult->orderBy('i.last_modified', 'desc');
            }
            /**
             * Pagination
             */
            if (isset($params['page']) && !empty($params['page']) && $params['is_export'] == false) {
                
                $objResult = $objResult->paginate($params['perPage']);
            } else {
                $objResult = $objResult->get();
            }

            //$query = \DB::getQueryLog();dd($query);
            $returnArray['queryResult'] = $objResult;
            $returnArray['permissions'] = $permissions;
            $returnArray['status'] = true;
            unset($searchColumn);
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            $returnArray['status'] = false;
            $returnArray['message'] = $exMsg;
        }
        
        return $returnArray;
    }
    
    /**
     * 
     * @param type $collection
     * @param type $headerType 0-> Normal Items ; 2-> Linked Items
     * @return type
     */
    function formatResult($collection, $headerType = 0) {
        
        $arrResponse = [];
        try {

            if (!empty($collection)) {                
                $arrResult = isset($collection['queryResult']) ? $collection['queryResult'] : [];                
                $arrPermissions = isset($collection['permissions']) ? $collection['permissions'] : [];
                $objChannels = new ChannelsDataSource();
                $objItemsDs = new ItemsDataSource();
                $objExportItemsSftpDs = new ExportItemsSftpDs();
                if($headerType == 0){
                    $arrChannels = $objChannels->getItemsChannelsAdtypes($intEventid = 0);
                }
                foreach ($arrResult as $val) {

                    $val = (object) $objItemsDs->filterStringDecode((array) $val);                                        
                    $val->is_row_edit = ItemsUtils::is_row_edit($arrPermissions, $val, true);
                    $val->is_excluded = ($val->is_excluded == '1') ? true : false;
                    $val->item_sync_status = ($val->item_sync_status == '1') ? true : false;
                    $val->publish_status = ($val->publish_status == '1') ? true : false;
                    $val->is_no_record = ($val->is_no_record == '1') ? true : false;
                    $val->cost = ItemsUtils::formatPriceValues($val->cost);
                    $val->base_unit_retail = ItemsUtils::formatPriceValues($val->base_unit_retail);
                    if($headerType == 2){
                        $val->items_import_source = ($val->items_import_source == '1') ? 'Import' : 'IQS';
                    }else{
                        unset($val->items_import_source);
                    }
                    if($headerType == 0){
                       
                        $val->price_id = trim(strtoupper($val->price_id));
                        $val->dotcom_price = ItemsUtils::formatPriceValues($val->dotcom_price);
                        $val->advertised_retail = ItemsUtils::formatAdRetaliValue($val->advertised_retail);
                        $val->was_price = ItemsUtils::formatPriceValues($val->was_price);
                        $val->save_amount = ItemsUtils::formatPriceValues($val->save_amount);
                        
                        $val->forecast_sales = ItemsUtils::formatPriceValues($val->forecast_sales);
                        $val->made_in_america = ItemsUtils::setDefaultNoValuesCol('made_in_america', $val->made_in_america);
                        $val->day_ship = ItemsUtils::setDefaultNoValuesCol('day_ship', $val->day_ship);
                        $val->co_op = ItemsUtils::setDefaultNoValuesCol('co_op', $val->co_op);
                        $val->landing_url = ItemsUtils::addhttp($val->landing_url);
                        $val->landing_url = PiLib::isValidURL($val->landing_url);
                        $val->item_image_url = PiLib::isValidURL($val->item_image_url);
                        $val->original_image = PiLib::isValidURL($val->item_image_url);
                        if (!empty($val->item_image_url)) {
                            $val->item_image_url = $val->item_image_url . config('smartforms.iqsThumbnail_60x60');
                        }
                        $val->dotcom_thumbnail = (!empty($val->dotcom_thumbnail)) ? PiLib::isValidURL($val->dotcom_thumbnail) : PiLib::isValidURL($val->original_image);
                        if (!empty($val->dotcom_thumbnail)) {
                            $val->dotcom_thumbnail = $val->dotcom_thumbnail . config('smartforms.iqsThumbnail_60x60');
                        }
                        $val->acitivity = $val->id;
                        $val->no_of_linked_item = $objExportItemsSftpDs->getNoOfLinkedItemsByUpc($val->events_id, $val->upc_nbr);
                        $val->adretails_highlight = ItemsUtils::getAdRetailSoldOutStaus($val);
                        $val->attributes = $objItemsDs->getAttributesSelectedValues($val->attributes);
                        $val->color_codes = ItemsUtils::getColorCodeValues($val->cell_color_codes);
                        $val->is_row_empty = ItemsUtils::isRowEmpty($val);
                        $val->grouped_item = !empty($val->grouped_item) ? $val->grouped_item : '';
                        $val->versions = $objExportItemsSftpDs->getVersionsByItemsId($val->id);
                        $val->mixed_column2 = $objExportItemsSftpDs->getOmitVersionsByItemId($val->id);
                    }
                    $arrResponse[] = array_merge((array) $val, isset($arrChannels[$val->id]) ? $arrChannels[$val->id] : []);
                    
                }
                unset($arrResult, $arrChannels);
            }
            
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            return $arrResponse = ['message' => $exMsg];
        }
        return array('itemValues' => $arrResponse, 'itemCount' => array('item' => count($arrResponse)));
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
//\DB::enableQueryLog();
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
//$query = \DB::getQueryLog();dd($query);
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
//$masterItemId = 0;

        if (isset($params['parent_id']) && !empty($params['parent_id'])) {
            $upcNbr = $this->getUpcNbrByParentItems($params['parent_id']);
        }
        \DB::enableQueryLog();
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
//                    if (isset($params['parent_id']) && !empty($params['parent_id'])) {
//                        $query->where('i.link_item_parent_id', $params['parent_id']);
//                    }
                    if (isset($params['parent_id']) && !empty($params['parent_id'])) {
                        $query->where('i.upc_nbr', $upcNbr);
                    }
//                    if (!empty($masterItemId)) {
//                        $query->whereIn('i.master_items_id', $masterItemId);
//                    }
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
// $query = \DB::getQueryLog();dd($query);
        return $objResult;
    }

    

 
  
}
 