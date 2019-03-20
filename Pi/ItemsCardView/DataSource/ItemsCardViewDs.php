<?php

namespace CodePi\ItemsCardView\DataSource;

use CodePi\Base\Eloquent\Items;
use CodePi\Base\Eloquent\Users;
use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\ItemsCardView\Commands\GetItemsCardView;
use CodePi\Channels\DataSource\ChannelsDataSource;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
use CodePi\Base\Eloquent\MasterDataOptions;
/**
 * Class : ItemsCardViewDs
 * This class will handle the Card View List and Search , MultiSorting
 */
class ItemsCardViewDs {

    public $itemDs;
    public $defaultColumns = ['sbu', 'size', 'brand_name', 'searched_item_nbr'];
    public $channelsDs;

    function __construct() {
        $this->itemDs = new ItemsDataSource();
        $this->channelsDs = new ChannelsDataSource();
    }

    /**
     * 
     * @param GetItemsCardView $command
     * @return boolean
     */
    function getCardViewData(GetItemsCardView $command) {

        DefaultIniSettings::apply();
        $params = $command->dataToArray();
        $returnArray = [];
        $totalCount = 0;
        try {
            $users_id = (isset($params['users_id']) && $params['users_id'] != 0) ? $params['users_id'] : $params['last_modified_by'];
            $departments_id = $this->getDepartmentId($users_id);
            $permissions = $this->itemDs->getAccessPermissions($users_id);
            $permissions['departments_id'] = $departments_id;
            $itemType = isset($params['item_type']) ? $params['item_type'] : '0';
            /*
             * Get the default item headers by order 
             */
            $getColumns = $this->itemDs->getItemDefaultHeaders($type = 0);
            //$getColumns = $this->getDefaultCardViewColumns($params['event_id'], $params['columns_array']);
            $column = $searchColumn = [];
            foreach ($getColumns as $key => $value) {
                $column[] = 'trim(' . $value['aliases_name'] . '.' . $key . ') as ' . $key;
                $searchColumn[] = $value['aliases_name'] . '.' . $key;
            }

            $columnName = implode($column, ',');            
            unset($column);
            $isAnd = true;
            //\DB::enableQueryLog();
            $objItems = new Items();           
            $objResult = $objItems->dbTable('i')
                                  ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                                  ->join('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                                  ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                                  ->leftJoin('items_groups as ig', 'i.id', '=', 'ig.items_id')
                                  ->leftJoin('items as child', function($join) use ($params) {
                                        $join->on('i.upc_nbr', '=', 'child.upc_nbr')
                                        ->where('child.items_type', '=', '1')
                                        ->where('child.events_id', $params['event_id']);
                                  })
                                  ->select('i.id', 'i.is_excluded', 'i.is_no_record', 'i.item_sync_status', 'i.publish_status', 'i.master_items_id', 'i.created_by', 'u.departments_id', 'i.cell_color_codes', 'i.last_modified', 'ie.page', 'ine.item_image_url', 'ie.ad_block', 'ie.versions', 'ine.signing_description',
                                           'i.searched_item_nbr', 'i.itemsid', 'i.plu_nbr', 'i.upc_nbr', 'i.fineline_number')
                                  ->selectRaw($columnName)
                                  ->selectRaw('IF(ie.versions IS NULL OR ie.versions = \'\', \'No Price Zone found.\' , ie.versions) as versions')
                                  ->selectRaw('count(child.upc_nbr) as link_count')
                                  ->selectRaw('IF(ie.grouped_item !=\'\', 1, 0) AS isGroupedItems')
                                  ->selectRaw('IF(ie.page IS NULL OR ie.page = \'\' , 0, ie.page) AS page ')
                                  ->selectRaw('ig.items_id AS parentGroup')
                                  ->selectRaw('(SELECT igi.items_id FROM items_groups_items AS igi WHERE igi.items_id = i.id LIMIT 1) AS childGroup')
                                  ->selectRaw('(SELECT mp.name FROM master_data_options AS mp WHERE mp.id = SUBSTRING_INDEX(ie.local_sources, ":",-1)) as local_sources')
                                  ->whereRaw('i.id NOT IN(SELECT igi.items_id FROM items_groups_items AS igi WHERE igi.items_id = i.id)')
                                  ->where(function ($query) use ($params) {
                                        if (isset($params['event_id']) && trim($params['event_id']) != '') {
                                            $query->where('i.events_id', $params['event_id']);
                                        }
                                  })->where(function ($query) use ($params) {
                                        if (isset($params['items_id']) && !empty($params['items_id'])) {
                                            $query->whereIn('i.id', $params['items_id']);
                                        }
                                  })->where(function ($query) use ($params) {
                                        if (isset($params['id']) && trim($params['id']) != '') {
                                            $query->where('i.id', $params['id']);
                                        }
                                  })->where(function ($query) use ($params) {
                                        if (isset($params['itemsListUserId']) && !empty($params['itemsListUserId'])) {
                                            $query->where('i.created_by', $params['itemsListUserId']);
                                        }
                                  })->where(function ($query) use ($params) {
                                        if (isset($params['department_id']) && !empty($params['department_id'])) {
                                            $query->where('u.departments_id', $params['department_id']);
                                        }
                                  })->where(function ($query) use ($searchColumn, $params, $isAnd) {
                                        /* add dynamic search conditions */
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
                                  })->groupBy('i.id');
            /**
             * Sorting
             */
           
            if(isset($params['multi_sort'][0]['column']) && !empty($params['multi_sort'][0]['column'])) {
                if(isset($params['multi_sort'][0]['aliases_name'])) {
                    $groupBy=$params['multi_sort'][0]['column'];
                    $alliasName=$params['multi_sort'][0]['aliases_name'];
                    // $objResult=$objResult->groupBy("$groupBy"); 
                    $objResult = $objResult->groupBy('' . $alliasName . '.' . $groupBy. '');  
                }
            }
            if (isset($params['multi_sort']) && !empty($params['multi_sort'])) {
                if(isset($params['multi_sort'][0]['aliases_name'])) {
                  $multipleOrderBy = $this->itemDs->addMultiSortConditions($params['multi_sort']);
                  $objResult = $objResult->orderByRaw(!empty($multipleOrderBy) ? $multipleOrderBy : 'cast(ie.page as unsigned) asc, ie.ad_block asc');
                }
            } else {
                $objResult = $objResult->orderByRaw('cast(ie.page as unsigned) asc, ie.ad_block asc');
            }
            /**
             * Pagination
             */
            //echo '<pre>'; print_r($params); die; 
            if (isset($params['page']) && !empty($params['page']) && !empty($params['perPage'])) {
                $objResult = $objResult->paginate($params['perPage']);
                $totalCount = $objResult->total();
            } else {
                $objResult = $objResult->get();
            }
            
            $objResult->totalCount = $totalCount;
            $returnArray['collection'] = $objResult;
            $returnArray['permissions'] = $permissions;
            //$query = \DB::getQueryLog();dd($query);
            unset($searchColumn);
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            throw new DataValidationException($exMsg, new MessageBag());
        }
        
        return $returnArray;
    }

    /**
     * This method will handle the assign default columns array, 
     * If users have permissions based columns, selected columns will check in $appColumns array,
     * If user don't have permission, $this->defaultColumns will be assigned as default columns
     * 
     * @param int $intEventId
     * @param array $arrColFilters
     * @return array
     */
    public function getDefaultCardViewColumns($intEventId = 0, $arrColFilters = []) {
        $arrDefaultCols = [];
        $defaultColKeys = !empty($arrColFilters) ? $arrColFilters : $this->defaultColumns;
        $params['events_id'] = $intEventId;
        $params['linked_item_type'] = 0;
        /**
         * Get Permissions based columns
         */
        $appColumns = $this->itemDs->getMappedItemHeaders($params);

        if (isset($appColumns['itemHeaders']) && !empty($appColumns['itemHeaders'])) {
            $totalCols = $appColumns['itemHeaders'];
            foreach ($totalCols as $value) {
                if (in_array($value['column'], $defaultColKeys)) {
                    $arrDefaultCols[$value['column']] = $value;
                }
            }
        } else {
            /**
             * If permissions based columns empty or not empty, load always default columns
             */
            $systemColumns = $this->itemDs->getItemDefaultHeaders($type = 0);
            if (!empty($systemColumns)) {
                foreach ($defaultColKeys as $key) {
                    if (isset($systemColumns[$key])) {
                        $arrDefaultCols[$key] = $systemColumns[$key];
                    }
                }
            }
            unset($appColumns, $systemColumns);
        }

        return $arrDefaultCols;
    }

    /**
     * Get Department id by given users id;
     * @param int $intUserID
     * @return int
     */
    function getDepartmentId($intUserID = 0) {        
        $objUsers = new Users();
        $objColl = $objUsers->where('id', $intUserID)->first();
        $departments_id = isset($objColl->departments_id) && !empty($objColl->departments_id) ? $objColl->departments_id : 0;
        return $departments_id;
    }

    /**
     * Get Channels and Adtypes based on events items
     * @param int $intEventId
     * @return array
     */
    function getMappedChannels($intEventId = 0) {
        $arrData  = [];
        $objItems = new Items();
//        $dbResult = $objItems->dbTable('i')
//                ->join('channels_items as ci', 'ci.items_id', '=', 'i.id')
//                ->join('channels as c', 'c.id', '=', 'ci.channels_id')
//                ->join('channels_ad_types as cat', function($join) use ($intEventId) {
//                    $join->on('cat.channels_id', '=', 'c.id')
//                    ->where('cat.id', '=', 'ci.channels_adtypes_id');
//                })
//                ->leftJoin('attachments as a', 'a.id', '=', 'c.attachments_id')
//                ->select('c.id', 'c.name', 'a.db_name', 'c.channel_logo', 'i.id')
//                ->selectRaw('GROUP_CONCAT(DISTINCT cat.name ORDER BY cat.name ASC) as ad_types')
//                ->where('i.events_id', $intEventId)                
//                ->groupBy('c.id')
//                ->get();

        $sql = "SELECT 
                c.id, c.name, GROUP_CONCAT(DISTINCT cat.name ORDER BY cat.name ASC) as ad_types,
                a.db_name, c.channel_logo, i.id as items_id, c.attachments_id
                FROM items AS i
                INNER JOIN channels_items AS ci ON ci.items_id = i.id
                INNER JOIN channels AS c ON c.id = ci.channels_id
                INNER JOIN channels_ad_types AS cat ON cat.channels_id = c.id AND cat.id = ci.channels_adtypes_id
                LEFT JOIN attachments as a on a.id = c.attachments_id
                WHERE i.events_id = " . $intEventId . " AND cat.`status` = '1'
                GROUP BY c.id, i.id";
        $collection = $objItems->dbSelect($sql);
        if (!empty($collection)) {
            foreach ($collection as $value) {
                $channleLogo = $this->channelsDs->setChannelLogo($value);
                $arrData[$value->items_id][] = array('channel_name' => $value->name, 'channel_logo' => $channleLogo, 'ad_types' => $value->ad_types);
            }
        }

        return $arrData;
    }
    /**
     * 
     * @param type $intEventId
     * @param type $arrColFilters
     * @return type
     */
    function getPermissionsColumns($intEventId = 0, $arrColFilters = []){
        $columns = $this->getDefaultCardViewColumns($intEventId, $arrColFilters);
        $arrResponse = [];
        if($columns){
            foreach ($columns as $key => $value){
                $arrResponse[] = ['lable' => $value['name'], 'key' => $key];
            }
        }
        return $arrResponse;
    }
    /**
     * 
     * @return type
     */
    function getMasterDataOptions() {
        $objMasterDataOptions = new MasterDataOptions();
        $arrMasterData = [];
        $masterInfo = $objMasterDataOptions->get(['id', 'name'])->toArray();
        if (!empty($masterInfo)) {
            foreach ($masterInfo as $key => $value) {
                $arrMasterData[$value['id']] = $value['name'];
            }
        }
        return $arrMasterData;
    }

}
