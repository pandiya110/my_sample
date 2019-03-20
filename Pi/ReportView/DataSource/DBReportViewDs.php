<?php

namespace CodePi\ReportView\DataSource;

use CodePi\ReportView\DataSource\DataSourceInterface\iItemsReportView;
use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Items;
#use CodePi\Base\Eloquent\Events;
use CodePi\Base\Eloquent\Users;
use GuzzleHttp;
#use CodePi\Base\Eloquent\ItemsHeaders;
#use CodePi\Base\Eloquent\ItemsEditable;
#use CodePi\Base\Eloquent\ItemsNonEditable;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Libraries\PiLib;
use Auth,
    Session;
use App\User;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Channels\DataSource\ChannelsDataSource;
use CodePi\Items\Utils\ItemsUtils;
use CodePi\Export\DataSource\ExportItemsSftpDs;
#use CodePi\Events\DataSource\EventsDataSource;


class DBReportViewDs implements iItemsReportView {
    /**
     * 
     * @param type $command
     * @return string
     */
    function getReportViewData($command) {

        $params = $command->dataToArray();
        $arrItems = [];
        try {
            $objItemsDs = new ItemsDataSource();
            $users_id = (isset($params['users_id']) && $params['users_id'] != 0) ? $params['users_id'] : $params['last_modified_by'];
            $objUsers = new Users();
            $userData = $objUsers->where('id', $users_id)->first();
            $departments_id = !empty($userData) ? $userData->departments_id : 0;
            $permissions = $objItemsDs->getAccessPermissions($users_id);
            $permissions['departments_id'] = $departments_id;
            $itemType = isset($params['item_type']) ? $params['item_type'] : '0';
            $headerType = ($params['item_type'] == '0') ? 0 : 2;

            /*
             * Get the default item headers by order 
             */
            $getColumns = $objItemsDs->getItemDefaultHeaders($headerType);
            $column = $searchColumn = [];
            foreach ($getColumns as $key => $value) {
                $column[] = 'trim(' . $value['aliases_name'] . '.' . $key . ') as ' . $key;
                $searchColumn[] = $value['aliases_name'] . '.' . $key;
            }
            //\DB::enableQueryLog();
            $columnName = implode($column, ',');
            unset($column);
            $isAnd = true;
            $objItems = new Items();

            $objResult = $objItems->dbTable('i')
                                  ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                                  ->join('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                                  ->join('events as e', 'e.id', '=', 'i.events_id')
                                  ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                                  ->select('i.id', 'i.is_excluded', 'i.is_no_record', 'i.item_sync_status', 'i.publish_status', 'i.master_items_id', 'i.created_by', 'u.departments_id', 'i.cell_color_codes', 'e.name as event_name', 'i.events_id', 'i.items_import_source')
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
                                  })->where('i.items_type', $itemType)->where('e.is_draft', '0')
//                                    ->where(function($query) use ($permissions, $params) {
//                                        if (isset($permissions['items_access']) && ($permissions['items_access'] == '6' || $permissions['items_access'] == '4' || $permissions['items_access'] == '5')) {
//
//                                        } else if (isset($permissions['items_access']) && ($permissions['items_access'] == '2' || $permissions['items_access'] == '3')) {
//                                            $query->where('u.departments_id', $permissions['departments_id']);
//                                        } else {
//                                            $query->where('i.created_by', $params['last_modified_by']);
//                                        }
//                                  })
                                   ->orderBy('i.last_modified', 'desc');

            /**
             * Pagination
             */
            if (isset($params['page']) && !empty($params['page']) && $params['is_export'] == false) {
                $objResult = $objResult->paginate($params['perPage']);
            } else {
                $objResult = $objResult->get();
            }

            //$query = \DB::getQueryLog();dd($query);
            $collection['queryResult'] = $objResult;
            $collection['permissions'] = $permissions;
            $arrItems = $this->formatResult($collection, $headerType);
            
            if (!empty($command->page) && $command->is_export == false) {
                $arrItems['count'] = $objResult->total();
                $arrItems['lastpage'] = $objResult->lastPage();
            }
            $arrItems['status'] = true;
            unset($searchColumn, $collection);
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            $arrItems['status'] = false;
            $arrItems['message'] = $exMsg;
        }

        return $arrItems;
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
                    $val->events_id = PiLib::piEncrypt($val->events_id);
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
                        $val->status = !empty($val->status) ? $val->status : '';
                        $val->local_sources = !empty($val->local_sources) && ($val->local_sources != 'Yes') ? 'No - '.$val->local_sources : 'Yes';
                        $val->priority = !empty($val->priority) ? $val->priority : '--';
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
                        //$val->no_of_linked_item = $objExportItemsSftpDs->getNoOfLinkedItemsByUpc(PiLib::piDecrypt($val->events_id), $val->upc_nbr);
                        $val->adretails_highlight = ItemsUtils::getAdRetailSoldOutStaus($val);
                        $val->attributes = $objItemsDs->getAttributesSelectedValues($val->attributes);
                        $val->color_codes = ItemsUtils::getColorCodeValues($val->cell_color_codes);
                        $val->is_row_empty = ItemsUtils::isRowEmpty($val);
                        $val->grouped_item = !empty($val->grouped_item) ? $val->grouped_item : '';
                        //$val->versions = $this->getSelectedVersions($val->id);
                        //$val->mixed_column2 = $objExportItemsSftpDs->getOmitVersionsByItemId($val->id);
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

}
