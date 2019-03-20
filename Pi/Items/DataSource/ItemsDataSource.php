<?php

namespace CodePi\Items\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Items;
use CodePi\Base\Eloquent\Events;
use CodePi\Base\Eloquent\Users;
use GuzzleHttp;
use CodePi\Base\Eloquent\ItemsHeaders;
use CodePi\Base\Eloquent\UsersItemHeaders;
use CodePi\Base\Eloquent\ItemsEditable;
use CodePi\Base\Eloquent\ItemsNonEditable;
use CodePi\Base\Eloquent\ItemsGroups;
use CodePi\Base\Eloquent\ItemsGroupsItems;
#use CodePi\Base\Eloquent\SystemsLogs;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Libraries\PiLib;
#use CodePi\Items\Commands\SaveLinkedItems;
use Auth,
    Session;
use App\User;
use App\Events\UpdateEventStatus;
#use App\Events\UpdateItemReSyncStatus;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Api\Commands\GetMasterItems;
use CodePi\Base\Eloquent\MasterItems;
use CodePi\Users\Commands\GetPermissions;
#use CodePi\Base\Libraries\FileReader;
use CodePi\Base\Libraries\FileReader\ReaderFactory;
use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\Base\Eloquent\MasterDataOptions;
use URL,
    DB;
#use CodePi\ItemsActivityLog\DataSource\ItemsActivityLogsDs;
use App\Events\ItemsActivityLogs;
use CodePi\Channels\DataSource\ChannelsDataSource;
use CodePi\Roles\DataSource\RolesDataSource;
#use CodePi\Api\DataSource\EmiApiDataSource;
use App\Events\IqsProgress;
use App\Events\ItemActions;
use CodePi\Items\DataSource\PriceZonesDataSource;
use CodePi\Items\DataSource\CopyItemsDataSource;
use CodePi\Items\DataSource\UsersColumnWidthDS;
use CodePi\Items\Utils\ItemsIQSRequest;
use CodePi\Templates\DataSource\UsersTemplatesDS;
use Illuminate\Support\Facades\Log;
use App\Events\UpdateLogDeleteItems;
use CodePi\ItemsActivityLog\Logs\ActivityLog;
//use CodePi\Import\DataSource\BulkImportItemsDs;
use CodePi\Base\Eloquent\EventsLiveUsers;
use CodePi\Base\Eloquent\EventsLiveUsersEdit;
use CodePi\Base\Eloquent\ItemsPriceZones;
use CodePi\Items\DataSource\GroupedDataSource;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
use CodePi\Items\Utils\ItemsUtils;
use CodePi\Campaigns\DataSource\CampaignsDataSource;
use CodePi\Users\DataSource\UsersData;

/**
 * Class : ItemsDataSource
 * Handle the Items Add/Edit/Listing and all Items Actions
 */
class ItemsDataSource {

    private $unique_id;

    function __construct() {
        $this->unique_id = mt_rand() . time();
    }

    /**
     * To get the Items count by users departments
     * 
     * @param type $command->id ; events id
     * @return Array $arrResponse
     */
    function getItemsByDepartments($command) {
        $params = $command->dataToArray();
        $users_id = (isset($params['users_id']) && $params['users_id'] != 0) ? $params['users_id'] : $params['last_modified_by'];
        $objUsers = new Users;
        $usrObj = $objUsers->where('id', $users_id)->first();
        $departments_id = isset($usrObj->departments_id) && !empty($usrObj->departments_id) ? $usrObj->departments_id : 0;

        $objEvents = new Events();
        $permissions = $this->getAccessPermissions($users_id);
        $permissions['departments_id'] = $departments_id;

        $dbResult = $objEvents->dbTable('e')
                ->join('items as i', 'i.events_id', '=', 'e.id')
                ->join('users as u', 'u.id', '=', 'i.created_by')
                ->join('departments as d', 'd.id', '=', 'u.departments_id')
                ->leftJoin('statuses as s', 's.id', '=', 'e.statuses_id')
                ->select('e.id AS event_id', 's.id AS status_id', 's.name AS status', 'u.id as user_id', 'd.name AS department_name', 'd.id as department', 'u.profile_image_url')
                ->selectRaw('count(i.events_id) AS item_count')
                ->selectRaw('concat(u.firstname, " ",u.lastname) as username')
                ->selectRaw('sum(if(i.publish_status = "0", 1, 0)) as unpublish_cnt')
                ->where(function ($query) use ($params) {
                    if (isset($params['id']) && trim($params['id']) != '') {
                        $query->where('e.id', $params['id'])->where('i.items_type', '0');
                    }
                })->where(function($query) use ($permissions, $params) {
                    if (isset($permissions['items_access']) && ($permissions['items_access'] == '6' || $permissions['items_access'] == '4' || $permissions['items_access'] == '5')) {
                        
                    } else if (isset($permissions['items_access']) && ($permissions['items_access'] == '2' || $permissions['items_access'] == '3')) {
                        $query->where('u.departments_id', $permissions['departments_id']);
                    } else {
                        $query->where('i.created_by', $params['last_modified_by']);
                    }
                })
                ->groupBy('d.id', 'u.id')
                ->orderBy('u.firstname', 'asc')
                ->get();

        return $dbResult;
    }

    /**
     * 
     * @param type $collection
     * @param type $updatedUserId
     * @return type
     */
    function formatItemsByDepartments($collection, $updatedUserId) {
        $arrResponse = $arrChildren = $arrEventID = array();
        if (!empty($collection)) {
            $totalItems = 0;
            foreach ($collection as $obj) {
                if (!empty($obj)) {
                    $ext = pathinfo($obj->profile_image_url, PATHINFO_EXTENSION);
                    if (empty($ext)) {
                        $profile_image = '';
                    } else {
                        $fileInfo = pathinfo($obj->profile_image_url);
                        $profile_image = URL::to($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '_small.' . $fileInfo['extension']);
                    }
                    $obj->status = ItemsUtils::setEventStatus($obj->status_id, $obj->item_count, $obj->unpublish_cnt, $obj->status);
                    $arrEventID[$obj->event_id] = $obj->event_id;
                    $arrResponse[$obj->event_id]['event_id'] = $obj->event_id;
                    $totalItems += $obj->item_count;
                    if ($updatedUserId == $obj->user_id) {

                        $arrResponse[$obj->event_id]['login_user'] = ['user_id' => $obj->user_id,
                            'username' => $obj->username,
                            'status' => $obj->status,
                            'status_id' => $obj->status_id,
                            'item_count' => $obj->item_count,
                            'profile_image' => $profile_image
                        ];
                    } else if ($updatedUserId != $obj->user_id) {

                        $arrChildren[$obj->event_id][$obj->department]['department'] = $obj->department_name;
                        $arrChildren[$obj->event_id][$obj->department]['department_id'] = $obj->department;
                        $arrChildren[$obj->event_id][$obj->department]['info'][] = ['user_id' => $obj->user_id,
                            'username' => $obj->username,
                            'status' => $obj->status,
                            'status_id' => $obj->status_id,
                            'item_count' => $obj->item_count,
                            'profile_image' => $profile_image
                        ];
                    }
                }
            }
            $arrResponse[$obj->event_id]['all_items'] = $totalItems;
            foreach ($arrEventID as $key) {
                $arrResponse[$key]['list'] = isset($arrChildren[$key]) ? array_values($arrChildren[$key]) : array();
            }
            unset($arrChildren, $arrEventID);
        }

        return array_values($arrResponse);
    }

    /**
     * This method will return the users custom orders items headers
     * @param type $userId
     * @return type array
     */
    function getUserItemsHeaders($userId = 0) {

        $arrResponse = [];
        $objUserItems = new UsersItemHeaders();
        /**
         * check logged in users headers orders
         */
        $objResult = $objUserItems->where('users_id', $userId)->get()->toArray();
        /**
         * if logged in users headers not exists, take default header orders
         */
        if (count($objResult) == 0) {
            $objResult = $objUserItems->where('users_id', 0)->get();
        }
        foreach ($objResult as $column) {
            $arrResponse[$userId] = json_decode($column->headers);
        }

        return $arrResponse;
    }

    /**
     * This method use to Map the Users order headers with default headers    
     * @param type $params
     * @return type array 
     */
    function getMappedItemHeaders($params) {

        $arrHeaders = $channels = [];
        $roleHeader = $channelArray = $finalArray = [];
        /**
         * Get Default Headers
         * @param int $params['linked_item_type']
         */
        $defaultHeaders = $this->getItemDefaultHeaders($params['linked_item_type'], $params['events_id']);
        /**
         * Get non visible columns
         */
        $objUsers = new UsersData();
        $nonVisibleCol = $objUsers->getNonvisibleColumns();

        foreach ($nonVisibleCol as $key => $header) {
            if (isset($defaultHeaders[$key])) {
                unset($defaultHeaders[$key]);
            }
        }


        /**
         * Get events channels, assign channels as grid columns
         * @param $params['events_id']
         */
        if ($params['linked_item_type'] != 2) {
            $objEvtChannels = new ChannelsDataSource();
            $channels = $objEvtChannels->getEventsChannels($params['events_id']);
            /**
             * Get Role level headers
             */
            $objRoleDs = new RolesDataSource();
            $roleHeader = $objRoleDs->getRoleMappedHeaders($params['events_id']);
            foreach ($roleHeader as $k => $v) {
                if (isset($v['column'])) {
                    if (array_key_exists(trim($v['column']), $nonVisibleCol)) {
                        unset($roleHeader[$k]);
                    }
                }
            }
        }

        if (!empty($roleHeader)) {

            foreach ($roleHeader as $header) {
                if (!isset($header['status'])) {
                    $arrHeaders[$header['order_no']] = $header;
                } else {
                    $arrHeaders['channel'] = $header;
                }
            }
            /**
             * merge the role headers with channels
             */
            $channelArray = [];
            if (isset($arrHeaders['channel'])) {
                if ($arrHeaders['channel']['status'] == '1') {
                    foreach ($channels as $row) {
                        $channelArray[$row['order_no']][] = $row;
                    }

                    $arrHeaders = $arrHeaders + $channelArray;
                    unset($arrHeaders['channel']);
                    ksort($arrHeaders);
                    $channelKey = array_keys($channelArray);

                    foreach ($arrHeaders as $key => $value) {
                        if (in_array($key, $channelKey)) {
                            foreach ($value as $val) {
                                $finalArray[] = $val;
                            }
                        } else {
                            $finalArray[] = $value;
                        }
                    }
                } else {
                    $finalArray = $roleHeader;
                    unset($finalArray['channel']);
                }
            }
            unset($arrHeaders, $channels, $channelArray);
        } else {
            /**
             * Set default header if role headers not available
             */
            foreach ($defaultHeaders as $key => $column) {
                $finalArray[] = $column;
            }
        }

        $array['itemHeaders'] = $finalArray;
        unset($finalArray);
        /**
         * Get users custom columns width
         */
        $intUsersId = (\Auth::check()) ? Auth::user()->id : 0;
        $objColWidth = new UsersColumnWidthDS();
        $customWidth = $objColWidth->getCustomColumnWidthByUserId($intUsersId);
        $array['itemHeadersWidth'] = ($customWidth) ? $customWidth : [];
        /**
         * Get hidden columns from template view
         */
        $objUserTempDs = new UsersTemplatesDS($intUsersId);
        $array['hiddenColumns'] = $objUserTempDs->getHiddenColumns($intUsersId);
        /**
         * Get Assigned template id
         */
        $array['currentTemplateId'] = $objUserTempDs->getActiveTemplateIdByUserId($intUsersId);

        unset($customWidth);
        return $array;
    }

    /**
     * This method will return default headers
     * if type = 0 Return Result items headers, type = 2 It will return Linked items headers
     * @param Enum $type
     * @param Integer $event_id
     * @return Array
     */
    function getItemDefaultHeaders($type, $event_id = 0) {

        $arrResponse = [];
        $objItemsHeaders = new ItemsHeaders();
        if ($type) {
            $linkedCol = ['1'];
        } else {
            $linkedCol = ['0', '1'];
        }
        $objResult = $objItemsHeaders->where('status', '1')
                ->whereIn('is_linked_item', $linkedCol)
                ->orderBy('column_order', 'asc')
                ->get();

        foreach ($objResult as $column) {
            $isEdit = ($column->is_editable == 1) ? TRUE : FALSE;
            $IsMandatory = ($column->is_mandatory == 1) ? TRUE : FALSE;
            $isFormat = ($column->format == 1) ? TRUE : FALSE;
            $IsCopy = ($column->is_copy == '1') ? TRUE : FALSE;
            $columnCount = ($column->column_count == '1') ? TRUE : FALSE;
            $arrResponse[$column->column_name] = ['id' => $column->id,
                'column' => $column->column_name,
                'color_code' => '#dadada',
                'channel_id' => '',
                'name' => $column->column_label,
                'IsEdit' => $isEdit,
                'type' => $column->field_type,
                'width' => $column->column_width,
                'IsMandatory' => $IsMandatory,
                'aliases_name' => $column->table_aliases_name,
                'column_source' => $column->column_source,
                'format' => $isFormat,
                'IsCopy' => $IsCopy,
                'columnCount' => $columnCount
            ];

            if ($column->field_type == 'dropdown' && $column->column_name != 'grouped_item') {
                $arrResponse[$column->column_name]['column_value'] = array_values($this->getMasterDataOptions($column->module_id));
            }
            if ($column->field_type == 'dropdown' && $column->column_name == 'local_sources') {
                $arrResponse[$column->column_name]['column_value'] = $this->getVendorSupplyOptions();
            }

            if ($column->column_name == 'grouped_item' && !empty($event_id)) {
                $arrResponse[$column->column_name]['column_value'] = $this->getGroupNameByEventId($event_id);
            }
        }

        return $arrResponse;
    }

    /**
     * Get List of Items given by Events ID
     * @param object $command
     * $params = $command->dataToArray() -> Covert object to array
     * @param  $params['users_id']      => This is reference of currently logged in users
     *                ['item_type']     => This flag  is to identify the linked items list or result items list
     *                ['event_id']      => This ID is will get only given events items list
     *                ['is_export']     => To identyfy the Export is require or not 
     *                ['export_option'] => This option is to get genenrate Export/CSV with Exclued items or not
     *                ['department_id'] => Logged users depratment id
     *                ['multi_sort']    => This params type is Array , this array will contain the multiple column to be sort  
     *                ['search']        => This params to search by given text values
     *                ['order']         => Order by colums
     *                ['sort']          => Sort by condtions (ASC or DESC)
     * @return Array
     */
    function getItemsGridData($command) {
        DefaultIniSettings::apply();
        $params = $command->dataToArray();
        try {
            $users_id = (isset($params['users_id']) && $params['users_id'] != 0) ? $params['users_id'] : $params['last_modified_by'];
            $objUsers = new Users;
            $usrObj = $objUsers->where('id', $users_id)->first();
            $departments_id = isset($usrObj->departments_id) && !empty($usrObj->departments_id) ? $usrObj->departments_id : 0;

            $filArray = PiLib::piIsset($params, 'filters', []);
            $permissions = $this->getAccessPermissions($users_id);
            $permissions['departments_id'] = $departments_id;
            $itemType = isset($params['item_type']) ? $params['item_type'] : '0';
            /*
             * Get the default item headers by order 
             */
            $getColumns = $this->getItemDefaultHeaders($linked_item_type = 0);
            $column = $searchColumn = [];
            foreach ($getColumns as $key => $value) {
                $column[] = 'trim(' . $value['aliases_name'] . '.' . $key . ') as ' . $key;
                $searchColumn[] = $value['aliases_name'] . '.' . $key;
            }
            \DB::enableQueryLog();
            $columnName = implode($column, ',');
            unset($column);
            $isAnd = true;
            $objItems = new Items();


            $objResult = $objItems->dbTable('i')
                    ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                    ->join('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                    ->leftJoin('users as u', 'u.id', '=', 'i.created_by');
            //if(isset($params['parent_item_id']) && empty($params['parent_item_id']) && empty($params['is_export'])){
            $objResult = $objResult->leftJoin('items_groups as ig', 'i.id', '=', 'ig.items_id');
            //$objResult = $objResult->leftJoin('items_groups_items as igi','i.id','=','igi.items_id');
            //}                    
            //->leftJoin('items_groups_items as igi','i.id','=','igi.items_id')                                                     
            $objResult = $objResult->leftJoin('items as child', function($join) use ($params) {
                        $join->on('i.upc_nbr', '=', 'child.upc_nbr')
                        ->where('child.items_type', '=', '1')
                        ->where('child.events_id', $params['event_id']);
                    })
                    ->select('i.id', 'i.is_excluded', 'i.is_no_record', 'i.item_sync_status', 'i.publish_status', 'i.master_items_id', 'i.created_by', 'u.departments_id', 'i.cell_color_codes', 'i.last_modified')
                    //->selectRaw('(select count(link_item_parent_id) from items where link_item_parent_id = i.id) as link_count')
                    ->selectRaw($columnName)
                    ->selectRaw('count(child.upc_nbr) as link_count')
                    ->selectRaw('IF(ie.grouped_item !=\'\', 1, 0) AS isGroupedItems')
                    ->selectRaw('ig.items_id AS parentGroup')
                    ->selectRaw('(SELECT igi.items_id FROM items_groups_items AS igi WHERE igi.items_id = i.id LIMIT 1) AS childGroup')
                    ->selectRaw('(SELECT mp.name FROM master_data_options AS mp WHERE mp.id = SUBSTRING_INDEX(ie.local_sources, ":",-1)) as local_sources');
            if (isset($params['parent_item_id']) && empty($params['parent_item_id']) && empty($params['is_export'])) {
                //$objResult = $objResult->where('igi.id',null);
                $objResult = $objResult->whereRaw('i.id NOT IN(SELECT igi.items_id FROM items_groups_items AS igi WHERE igi.items_id = i.id)');
            }
            $objResult = $objResult->where(function ($query) use ($params) {
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
            if (isset($params['multi_sort']) && !empty($params['multi_sort'])) {
                $multipleOrderBy = $this->addMultiSortConditions($params['multi_sort']);
                $objResult = $objResult->orderByRaw(!empty($multipleOrderBy) ? $multipleOrderBy : 'i.last_modified desc');
            } else if (isset($params['order']) && !empty($params['order']) && (isset($params['sort']) && !empty($params['sort']))) {
                $activityColumn = ['is_no_record', 'is_excluded', 'item_sync_status'];
                $and = "";
                $aliase = $this->findTableAliaseName($params['order']);
                $data_type = (isset($aliase['type']) && $aliase['type'] == 'numeric') ? 'unsigned' : 'char';
                $sort = in_array($params['order'], $activityColumn) ? 'desc' : $params['sort'];
                if ($params['order'] == 'no_of_linked_item') {
                    $objResult = $objResult->orderBy('link_count', $sort);
                } else if ($params['order'] == 'grouped_item') {
                    $objResult = $objResult->orderBy('isGroupedItems', $sort);
                } else {

                    $objResult = $objResult->orderByRaw('cast(' . $aliase['aliase'] . '.' . $params['order'] . ' as ' . $data_type . ') ' . $sort . '');
                }
            } else {
                $objResult = $objResult->orderBy('i.last_modified', 'desc');
            }
            /**
             * Pagination
             */
            if (isset($params['page']) && !empty($params['page']) && !empty($params['perPage']) && $params['is_export'] == false) {
                $objResult = $objResult->paginate($params['perPage']);
            } else {
                $objResult = $objResult->get();
            }

            //$query = \DB::getQueryLog();dd($query);
            $returnArray['objResult'] = $objResult;
            $returnArray['permissions'] = $permissions;
            $returnArray['status'] = true;
            unset($searchColumn, $filArray);
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            $returnArray['status'] = false;
        }
        return $returnArray;
    }

    /**
     * Adding Multiple sort conditions
     * @param array $columnsArray
     * @return string
     */
    function addMultiSortConditions($columnsArray) {
        $colums = [];
        $orderBy = "";
        if (!empty($columnsArray)) {

            $priceColumns = ['dotcom_price', 'advertised_retail', 'was_price', 'save_amount', 'base_unit_retail', 'cost', 'forecast_sales'];
            foreach ($columnsArray as $values) {
                $fieldType = $this->findTableAliaseName($values['column']);

                /**
                 * Make it page column as a numeric dat type
                 */
                if ($values['column'] == 'page') {
                    $fieldType['type'] = 'numeric';
                }

                if ($fieldType['type'] == 'numeric') {

                    /**
                     * Remove $ symbol from price value columns
                     */
                    if (in_array($values['column'], $priceColumns)) {
                        $values['column'] = 'cast(replace(' . $fieldType['aliase'] . '.' . $values['column'] . ',\'$\',\'\') as decimal(10,2)) ' . $values['order'];
                    } else if ($values['column'] == 'no_of_linked_item') {
                        $values['column'] = 'link_count ' . $values['order'];
                    } else {
                        $values['column'] = 'cast(' . $fieldType['aliase'] . '.' . $values['column'] . ' as unsigned) ' . $values['order'];
                    }
                } else {
                    $values['column'] = $fieldType['aliase'] . '.' . $values['column'] . ' ' . $values['order'];
                }

                $colums[] = $values['column'];
            }

            $orderBy = implode(", ", $colums);
            unset($colums);
        }

        return $orderBy;
    }

    /**
     * Get Master Items
     * 
     * @param object $command
     * @return  Array
     */
    function getMasterItemsByVersion($command) {

        $params = $command->dataToArray();
        $userEditable = $params['userEditable'];
        $is_price_req = $params['is_price_req'];
        /**
         * Get the Master Items data
         */
        $objCommand = new GetMasterItems(['item_nbr' => $params['items'], 'search_key' => $params['search_key']]);
        $masterData = CommandFactory::getCommand($objCommand);

        $allItemData = [];

        foreach ($masterData as $values) {
            if (count($userEditable) > 0) {
                foreach ($userEditable as $key => $v) {
                    //echo $key;
                    //if(isset($values[$key])){
                    //if($v!=''){
                    $values[$key] = $v;
                    //}
                    //}
                }
            }

            $allItemData[trim($values['itemsid'])] = $values;
        }
        //$allItemData_notDb = getItemsNotExistsInDb($command,$allItemData);
        $allMasterData = [];
        if ($is_price_req == 1) {
            $allMasterData = $this->getVersionData($allItemData);
        }

        foreach ($allItemData as $k => $val) {
            if (!isset($allMasterData[$k])) {
                $val['versions'] = ($is_price_req == 1) ? ['No Price Zone found.'] : ['No Price Zone available.'];
                $val['advertised_retail'] = '';
                $val['was_price'] = '';
                $allMasterData[$k][] = $this->applyPriceIdRules($val);
            }
        }

        return $allMasterData;
    }

    /**
     * 
     * @param type $command
     * @param type $items
     * @return array
     */
    function getItemsNotExistsInDb($command, $items) {
        $params = $command->dataToArray();
        $objItems = new Items();
        $notDbItems = [];
        $itemsKeys = array_keys($items);
        $result = $objItems->whereIn('itemsid', $itemsKeys)->where('events_id', $params['event_id'])
                ->where('created_by', '!=', $params['last_modified_by'])->groupBy('itemsid')
                ->get(['itemsid']);

        foreach ($result as $row) {
            $itemsid = trim($row->itemsid);
            if (isset($items[$itemsid])) {
                unset($items[$itemsid]);
            }
        }
        return $notDbItems;
    }

    /**
     * Read the data from pricezone excel
     * @param type $file
     * @return array
     */
    function getData($file) {
        DefaultIniSettings::apply();
        $objReader = ReaderFactory::select($file);
        return $objReader->getData($file, true);
    }

    /**
     * Find the Versions from Price zone excel
     * 
     * @param array $allItemData
     * @return array
     */
    function getVersionData($allItemData) {
        $allVersionData = [];
//        $filename = storage_path('app') . '/public/PriceZone/PriceZones.xlsx';
//        $getData = $this->getData($filename);
//        $header = $getData[0][1];
//
//        $exelData = $getData[0];
//
//        unset($exelData[1]);
//
//        foreach ($exelData as $row) {
//
//            $itemsid = $row[0];
//            if (isset($allItemData[$itemsid])) {
//
//                $ItemDataVersion = [];
//                $ItemDataVersion = $allItemData[$itemsid];
//                $versions = $this->getVersionForItem($row, $header);
//                $ItemDataVersion['versions'] = $versions; //GuzzleHttp\json_encode($versions);
//                $ItemDataVersion['advertised_retail'] = isset($row[1]) ? $row[1] : '';
//                $ItemDataVersion['was_price'] = isset($row[2]) ? $row[2] : '';
//                $allVersionData[$itemsid][] = $this->applyPriceIdRules($ItemDataVersion);
//            }
//        }

        return $allVersionData;
    }

    /**
     * Set Price rules based on price id values
     * 
     * @param array $data
     * @return array
     */
    function applyPriceIdRules($data) {

        $retunData = $data;
        $priceIdArray = ['EDLP', 'ROLLBACK', 'SPECIAL BUY', 'BONUS', 'SPECIAL VALUE'];

        $priceId = strtoupper(trim($data['price_id']));
        $ad_retail = is_numeric($data['advertised_retail']) ? $data['advertised_retail'] : 0;
        $was_price = is_numeric($data['was_price']) ? $data['was_price'] : 0;
        /**
         * if price id not present, set defaut price id
         */
        if (!in_array($priceId, $priceIdArray)) {
            $retunData['special_value'] = '';
            if ($ad_retail > $was_price) {
                $priceId = 'EDLP';
            } else {
                $priceId = 'EDLP'; //'ROLLBACK';
            }
            $retunData['price_id'] = $priceId;
        }

        /**
         * Set was price value
         */
        if ($priceId == 'EDLP') {
            $retunData['was_price'] = '';
        } else if ($priceId == 'ROLLBACK') {
            if ($was_price > 0) {
                $retunData['save_amount'] = $was_price - $ad_retail;
            } else {
                $retunData['save_amount'] = '';
            }
        } else if ($priceId == 'SPECIAL BUY') {
            $retunData['was_price'] = '';
        } else if ($priceId == 'BONUS') {
            $retunData['was_price'] = '';
        }

        return $retunData;
    }

    /**
     * Format the Items Versions
     * 
     * @param array $data
     * @param array $header
     * @return array
     */
    function getVersionForItem($data, $header) {
        $version = [];
        if (isset($data[0]))
            unset($data[0]);
        if (isset($data[1]))
            unset($data[1]);
        if (isset($data[2]))
            unset($data[2]);

        foreach ($data as $k => $v) {
            if (isset($header[$k]) && $v != '') {
                $version[] = $header[$k] . ' - ' . $v;
            }
        }

        return $version;
    }

    /**
     * Delete the If items already exists , by selected events
     * @param object $command
     * @return type
     */
    function deleteExistsItems($event_id, $itemsid) {
        $objItems = new Items();
        $objItems->dbTransaction();
        try {
            //$objItems->whereIn('itemsid', $itemsid)->where('events_id', $event_id)->delete();
            $objItems->whereIn('master_items_id', $itemsid)->where('events_id', $event_id)->delete();
            $objPrcZoneDs = new PriceZonesDataSource();
            $objPrcZoneDs->deleteVersionsByItemsId(0, $itemsid, $event_id);
            $objItems->dbCommit();
        } catch (\Exception $ex) {
            $objItems->dbRollback();
        }
        return true;
    }

    /**
     * conver utf 8 characters
     * @param array $array
     * @return array
     */
    function filterString(array $array) {
        $arrayData = [];
        if (is_array($array)) {
            foreach ($array as $key => $str) {
                $arrayData[$key] = PiLib::filterString($str);
            }
            return $arrayData;
        } else {
            return $array;
        }
    }

    /**
     * Decode the html entites values
     * @param array $array
     * @return array
     */
    function filterStringDecode($array) {
        $arrString = [];
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $arrString[$key] = PiLib::filterStringDecode($value);
            }
            return $arrString;
        } else {
            return $array;
        }
    }

    /**
     * Add the new items from master data
     * 
     * @param object $command
     * @return array
     */
    function saveItems($command) {

        $params = $command->dataToArray();
        $BulkImportItemsDsObj = new \CodePi\Import\DataSource\BulkImportItemsDs;
        $totalCnt = $insertCnt = $updateCnt = $addCount = 0;
        $itemData = $saveIds = $noRecord = $responseData = $saveLinkIds = $deleted_items = $notExitsMaster = [];
        $i = 0;
        $noDataFound = 0;
        $itemsArray = $params['items'];
        $requestCount = count($params['items']);
        $users_id = isset($params['users_id']) ? $params['users_id'] : $params['last_modified_by'];
        $commonData = [
            'created_by' => $users_id,
            'last_modified_by' => $users_id,
            'date_added' => isset($params['date_added']) ? $params['date_added'] : date('Y-m-d H:i:s'),
            'last_modified' => $params['last_modified'],
            'last_modified_by' => $params['last_modified_by'],
            'gt_date_added' => isset($params['gt_date_added']) ? $params['gt_date_added'] : gmdate("Y-m-d H:i:s"),
            'gt_last_modified' => $params['gt_last_modified'],
            'ip_address' => isset($params['new_ip_address']) ? $params['new_ip_address'] : $params['ip_address'],
            'tracking_id' => $this->unique_id . '-0'
        ];

        if (isset($params['is_price_req']) && $params['is_price_req'] == 1) {
            $deleted_items = $this->addActivityLogDeleExistsItems($command, $commonData);
        }
        /**
         * Get the HistoricalReferenceDate
         */
        $objEventsDs = new \CodePi\Events\DataSource\EventsDataSource;
        $historicalDates = $objEventsDs->getHistoricalReferenceDate($params['event_id']);
        /**
         * Update aprimo details
         */
        $aprimoDetails = $objEventsDs->getAprimoDetails($params['event_id']);

        foreach ($itemsArray as $searchValue) {
            $saveItemsID = 0;
            if (isset($params['users_id']) && !empty($params['users_id'])) {
                if ($i == 0) {
                    $objItems = ['users_id' => $users_id,
                        'events_id' => PiLib::piEncrypt($params['event_id']),
                        'progress' => 0,
                        'count' => $i,
                        'total' => $requestCount,
                        'message' => $i . ' Items Added',
                        'status' => false,
                        'error' => false
                    ];
                    event(new IqsProgress($objItems));
                }
            }
            $params['items'] = [$searchValue];
            /**
             * Api call for to get the items data from api
             */
            $objIQS = new ItemsIQSRequest($params['items'], $params['search_key']);
            $objIQS->pullItemsFromIQSApi();

            $command->items = [$searchValue];
            $masterData = $this->getMasterItemsByVersion($command);
            
            $itemsid = array_keys($masterData);
            $is_price_req = $params['is_price_req'];

            /**
             * If search items not exists in master, insert data with no record status
             */
            $noRecord = $this->saveNoRecordData($params, $commonData);
            $saveItemsID = $noRecord;
            $noDataFound += ($noRecord == 0) ? 0 : 1;
            $notExitsMaster[] = $noRecord;

            \DB::beginTransaction();
            try {
                foreach ($masterData as $key => $data) {
                    $master_versions = [];
                    foreach ($data as $row) {
                        $objItems = new Items();
                        //$objPrcZoneDs = new PriceZonesDataSource();
                        $row['master_items_id'] = $row['id'];
                        unset($row['id']);
                        $row = $this->filterString($row);
                        $itemData = $row;

                        $itemData = array_merge($commonData, $row);
                        $itemData['events_id'] = $params['event_id'];
                        $itemData['versions_code'] = GuzzleHttp\json_encode($row['versions']);
                        $saveID = $objItems->saveRecord($itemData);
                        $versionsCode = ''; //$objPrcZoneDs->saveManualVersions(array_merge($commonData, array('item_id' => $saveID->id, 'events_id' => $params['event_id'], 'versions' => $row['versions'], 'type' => 2)));
                        $itemsEditData = array_merge($commonData, $row);
                        $itemsEditData['items_id'] = $saveID->id;
                        $itemsEditData['page'] = $BulkImportItemsDsObj->setStringPad($itemData['page'], 2);
                        $itemsEditData['status'] = ($itemData['status'] != 1) ? $itemData['status'] : '';
                        $itemsEditData['versions'] = isset($versionsCode['versions']) && !empty($versionsCode['versions']) ? implode(", ", $versionsCode['versions']) : "No Price Zone found.";
                        $itemsEditData['event_dates'] = $historicalDates;
                        $itemsEditData['aprimo_campaign_id'] = isset($aprimoDetails['aprimo_campaign_id']) ? $aprimoDetails['aprimo_campaign_id'] : '';
                        $itemsEditData['aprimo_campaign_name'] = isset($aprimoDetails['aprimo_campaign_name']) ? $aprimoDetails['aprimo_campaign_name'] : '';
                        $itemsEditData['aprimo_project_id'] = isset($aprimoDetails['aprimo_project_id']) ? $aprimoDetails['aprimo_project_id'] : '';
                        $itemsEditData['aprimo_project_name'] = isset($aprimoDetails['aprimo_project_name']) ? $aprimoDetails['aprimo_project_name'] : '';
                        $this->saveItemsEditData($itemsEditData);

                        $itemsNonEdit = array_merge($commonData, $row);
                        $itemsNonEdit['items_id'] = $saveID->id;
                        $itemsNonEdit['gtin_nbr'] = !empty($itemsNonEdit['gtin_nbr']) ? str_pad(trim($itemsNonEdit['gtin_nbr']), 14, '0', STR_PAD_LEFT) : '';
                        $this->saveItemsNonEditData($itemsNonEdit);
                        $saveItemsID = $saveID->id;
                        $saveIds[] = $saveID->id;
                        $master_versions = array_merge($master_versions, $row['versions']);
                        $addCount++;
                        $saveLinkIds[] = $this->saveLinkedItemsFromMasterItems(['items_id' => $saveID->id, 'master_items_id' => $itemData['master_items_id'], 'events_id' => $itemData['events_id'], 'itemNbr' => $itemData['searched_item_nbr'], 'upc_nbr' => $itemData['upc_nbr']], $commonData);
                    }
                    //$this->updateMasterVersionByItemsid($key, $master_versions);
                }

                //$this->updateIsMovableStatus($saveIds);
                $i++;
                $status = false;
                $progress = ($i * 100 / $requestCount);
                if ($i == $requestCount) {

                    /**
                     * construct params to get the save items 
                     */
                    $totalLinkedItms = 0;
                    foreach ($saveLinkIds as $count) {
                        $totalLinkedItms += count($count);
                    }
                    $arrParams = ['addCount' => $addCount, 'event_id' => $params['event_id'], 'deletedItems' => isset($deleted_items['deleteItems']) ? $deleted_items['deleteItems'] : [],
                        'users_id' => $users_id, 'noDataFound' => $noDataFound, 'notExitsMaster' => $notExitsMaster,
                        'saveIds' => $saveIds, 'saveLinkIds' => $totalLinkedItms, 'deletedLinkItems' => isset($deleted_items['deleteLinkItems']) ? $deleted_items['deleteLinkItems'] : 0
                    ];

                    $responseData = $this->getSaveItemsResponse($arrParams, $commonData);

                    $status = true;
                } else {
                    $status = false;
                }


                if (isset($params['users_id']) && !empty($params['users_id'])) {
                    $objItems = ['users_id' => $users_id,
                        'events_id' => PiLib::piEncrypt($params['event_id']),
                        'progress' => $progress,
                        'count' => $i,
                        'total' => $requestCount,
                        'message' => $i . ' Items Added',
                        'status' => $status,
                        'response' => $responseData,
                        'error' => false
                    ];
                    $is_completed = ($requestCount == $i) ? true : false;
                    if ($saveItemsID != 0) {
                        $arrItemInfo = $BulkImportItemsDsObj->sendDataToBroadCast(array($saveItemsID), $params['event_id'], $is_completed);
                        $arrItemInfo['deleted_items'] = isset($deleted_items['deleteItems']) ? $deleted_items['deleteItems'] : [];
                        broadcast(new ItemActions($arrItemInfo, 'addItem'))->toOthers();
                    }
                    event(new IqsProgress($objItems));
                }

                \DB::commit();
            } catch (\Exception $ex) {
                \DB::rollback();
                $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
                CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
                $arrEventData = ['users_id' => $users_id, 'events_id' => PiLib::piEncrypt($params['event_id']), 'status' => true, 'error' => true, 'message' => $exMsg];
                event(new IqsProgress($arrEventData));
            }
        }

        unset($arrParams, $itemData, $saveIds, $noRecord, $saveLinkIds, $deleted_items, $notExitsMaster);

        return $responseData;
    }

    /**
     * Get the response of saved items
     * @param array $arrParams
     * @param array $commonData
     * @return array
     */
    function getSaveItemsResponse(array $arrParams, array $commonData) {

        $response = [];
        /**
         * Update Events Status
         */
        $this->updateEventStatus($arrParams['event_id']);

        $totalCnt = $arrParams['addCount'] + $arrParams['noDataFound'];
        $updateCnt = count($arrParams['deletedItems']);
        $insertCnt = ($arrParams['addCount'] - $updateCnt) > 0 ? $arrParams['addCount'] - $updateCnt : 0;

        /**
         * Track the activity logs
         */
        if (($totalCnt - $updateCnt) > 0) {
            $logsData = array_merge($commonData, ['events_id' => $arrParams['event_id'], 'actions' => 'insert', 'tracking_id' => $this->unique_id . '-0', 'users_id' => $arrParams['users_id'], 'descriptions' => ($totalCnt - $updateCnt) . ' Items Inserted']);
            event(new ItemsActivityLogs($logsData));
        }
        if (isset($arrParams['saveLinkIds']) && !empty($arrParams['saveLinkIds'])) {
            if (($arrParams['saveLinkIds'] - $arrParams['deletedLinkItems']) > 0) {

                $insertLink = $arrParams['saveLinkIds'] - $arrParams['deletedLinkItems'];
                $logsData['descriptions'] = $insertLink . ' Linked Items Inserted';
                $logsData['type'] = '1';
                $logsData['tracking_id'] = $this->unique_id . '-1';
                event(new ItemsActivityLogs($logsData));
            }
        }

        $countResponse = ['totalCnt' => $totalCnt,
            'insertCnt' => $insertCnt,
            'updateCnt' => $updateCnt,
            'items_id' => array_filter(array_merge($arrParams['saveIds'], $arrParams['notExitsMaster'])),
            'deleted_items' => $arrParams['deletedItems'],
            'noDataFound' => $arrParams['noDataFound']
        ];

        if (!empty($countResponse['items_id'])) {
            $data['items_id'] = array_filter($countResponse['items_id']);
            $data['event_id'] = PiLib::piEncrypt($arrParams['event_id']);
            $data['users_id'] = $commonData['created_by'];
            $objCommand = new GetItemsList($data);
            $cmdResponse = CommandFactory::getCommand($objCommand);
            $response['status'] = true;
            $response = array_merge($cmdResponse['items'], $countResponse);
        }
        unset($arrParams, $commonData);
        return $response;
    }

    /**
     * 
     * @param type $itemsId
     * @param type $versions
     * @return boolean
     */
    function updateMasterVersionByItemsid($itemsId, $versions) {

        $objMasterItems = new MasterItems();
        $objMasterItems->dbTransaction();
        try {
            $dbResult = $objMasterItems->where('itemsid', $itemsId)->first();
            if (!empty($dbResult)) {
                $master_versions = !empty($dbResult->version_code) ? GuzzleHttp\json_decode($dbResult->version_code) : [];
                $master_versions = array_merge($master_versions, $versions);
                $master_versions = array_unique($master_versions);
                $master_versions_json = \GuzzleHttp\json_encode($master_versions);
                $objMasterItems->where('id', $dbResult->id)->update(['version_code' => $master_versions_json]);
            }
            $objMasterItems->dbCommit();
        } catch (\Exception $ex) {
            $objMasterItems->dbRollback();
        }
        return true;
    }

    /**
     * 
     * @param type $data
     * @return type
     */
    function saveItemsEditData($data) {
        $objEditItems = new ItemsEditable();
        $saveEdit = [];
        $objEditItems->dbTransaction();
        try {
            $saveEdit = $objEditItems->saveRecord($data);
            $objEditItems->dbCommit();
        } catch (\Exception $ex) {
            $objEditItems->dbRollback();
        }
        return $saveEdit;
    }

    /**
     * 
     * @param type $data
     * @return type
     */
    function saveItemsNonEditData($data) {
        $objItemsNonEditable = new ItemsNonEditable();
        $saveNonEdit = [];
        $objItemsNonEditable->dbTransaction();
        try {
            $saveNonEdit = $objItemsNonEditable->saveRecord($data);
            $objItemsNonEditable->dbCommit();
        } catch (\Exception $ex) {
            $objItemsNonEditable->dbRollback();
        }
        return $saveNonEdit;
    }

    /**
     * 
     * @param type $params
     * @param type $commonData
     * @return type
     */
    function saveNoRecordData($params, $commonData) {
        $objItems = new Items();
        $objItems->dbTransaction();
        $saveData = 0;
        try {
            $params['item_nbr'] = $params['items'];
            $objCommand = new GetMasterItems($params);
            $masterData = CommandFactory::getCommand($objCommand);

            if (empty($masterData)) {
                $noRecord = isset($params['items']) ? array_shift($params['items']) : [];
                if (!empty($noRecord)) {
                    $insertData = [$params['search_key'] => $noRecord, 'is_no_record' => '1'];
                    $itemData = array_merge($commonData, $insertData);
                    $itemData['events_id'] = $params['event_id'];
                    $saveID = $objItems->saveRecord($itemData);

                    $itemsEditData['items_id'] = $saveID->id;
                    $itemsEditData = array_merge($commonData, $itemsEditData);
                    $this->saveItemsEditData($itemsEditData);

                    $itemsNonEdit['items_id'] = $saveID->id;
                    $itemsNonEdit = array_merge($commonData, $itemsNonEdit);
                    $this->saveItemsNonEditData($itemsNonEdit);
                    $saveData = $saveID->id;
                }
            }
            $objItems->dbCommit();
        } catch (\Exception $ex) {
            $objItems->dbRollback();
        }

        return $saveData;
    }

    /**
     * Update event status
     * @param int $params['statuses_id'] Status ID
     * @param int $$params['event_id'] EventID
     */
    function updateEventStatus($event_id) {
        $itemCount = $this->getItemsCount($event_id);
        if (isset($itemCount['item']) && $itemCount['item'] > 0) {
            $params['statuses_id'] = 2;
        }
        if ($itemCount['item'] > 0) {

            $isPublish = $this->isPublishedEvents($event_id);
            if ($isPublish == 0) {
                $params['statuses_id'] = 3;
            } else {
                $params['statuses_id'] = 2;
            }
        }
        event(new UpdateEventStatus($params['statuses_id'], $event_id));
        unset($params['statuses_id']);
    }

    /**
     * This method will handle the delete the items informations
     * @param object $command
     * @return array boolean
     */
    function deleteEventItem($command) {
        $params = $command->dataToArray();
        $objItems = new Items();
        $objEdit = new ItemsEditable();
        $objItemsNonEditable = new ItemsNonEditable();
        $status = false;
        $objItems->dbTransaction();
        try {
            if (is_array($params['id'])) {
                $dbResult = $objItems->whereRaw('link_item_parent_id in(' . '\'' . implode("','", $params['id']) . '\'' . ') or id in(' . '\'' . implode("','", $params['id']) . '\'' . ')')->get(['id', 'items_type'])->toArray();
                foreach ($dbResult as $row) {
                    $objUPCResult = $objItems->select('upc_nbr')
                                    ->where('id', $row['id'])->get();

                    if (isset($objUPCResult[0]) && !empty($objUPCResult[0]['upc_nbr'])) {
                        $otherItems = $objItems->where('upc_nbr', $objUPCResult[0]['upc_nbr'])
                                        ->where('id', '!=', $row['id'])
                                        ->where('events_id', $params['event_id'])
                                        ->where('items_type', '0')->get();
                    }

                    if (isset($otherItems[0]) && empty($otherItems[0])) {
                        $objLinkedItems = $objItems->where('upc_nbr', $objUPCResult[0]['upc_nbr'])
                                        ->where('events_id', $params['event_id'])
                                        ->where('items_type', '1')->get();
                        foreach ($objLinkedItems as $linkedItems) {
                            $objItems->where('id', $linkedItems['id'])->delete();
                            $objEdit->where('items_id', $linkedItems['id'])->delete();
                            $objItemsNonEditable->where('items_id', $linkedItems['id'])->delete();
                        }
                    }

                    $objItems->where('id', $row['id'])->delete();
                    $objEdit->where('items_id', $row['id'])->delete();
                    $objItemsNonEditable->where('items_id', $row['id'])->delete();

                    $countBy[$row['items_type']][] = $row['id'];
                }
            }
            /**
             * Delete record from grouped items
             */
            $objItemsGroups = new ItemsGroups();
            $objItemsGroupsItems = new ItemsGroupsItems();
            $objItemsGroups->whereIn('items_id', $params['id'])->delete();
            $objItemsGroupsItems->whereIn('items_id', $params['id'])->delete();

            $objPrcZoneDs = new PriceZonesDataSource();
            $objPrcZoneDs->deleteVersionsByItemsId($params['id'], 0, 0);

            $objChannelDs = new ChannelsDataSource();
            $objChannelDs->deleteChannelsItemsAdTypes($params['id']);

            /**
             * Update the Events status based on Items count & publish status
             * @params $params['event_id']
             * @params $data['statuses_id']
             */
            $itemCount = $this->getItemsCount($params['event_id']);
            if (empty($itemCount['item'])) {
                $params['statuses_id'] = 1;
            } else if ($itemCount['item'] > 0) {
                $isPublish = $this->isPublishedEvents($params['event_id']);
                if ($isPublish == 0) {
                    $params['statuses_id'] = 3;
                } else {
                    $params['statuses_id'] = 2;
                }
            }
            /**
             * Update event status
             */
            event(new UpdateEventStatus($params['statuses_id'], $params['event_id']));
            $status = true;
            /**
             * Track the activity logs details
             */
            $itemCnt = isset($countBy[0]) ? count($countBy[0]) : 0;
            $linkItmCnt = isset($countBy[1]) ? count($countBy[1]) : 0;

            $objLogs = new ActivityLog();
            $logData = $objLogs->setActivityLog(array('events_id' => $params['event_id'], 'actions' => 'delete', 'users_id' => $params['last_modified_by'], 'count' => $itemCnt, 'type' => '0', 'tracking_id' => $this->unique_id, 'items_id' => isset($countBy[0]) ? $countBy[0] : []));
            $logData = $objLogs->setActivityLog(array('events_id' => $params['event_id'], 'actions' => 'delete', 'users_id' => $params['last_modified_by'], 'count' => $linkItmCnt, 'type' => '1', 'tracking_id' => $this->unique_id, 'items_id' => isset($countBy[1]) ? $countBy[1] : []));
            $objLogs->updateActivityLog($logData);
            unset($logData);

            $objItems->dbCommit();
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            $status = false;
            $objItems->dbRollback();
        }
        return $status;
    }

    /**
     * This method will handle the exclude/include items from itemgrid list
     * @param object$command
     * @return array boolean
     */
    function excludeEventItem($command) {

        $params = $command->dataToArray();
        $objItems = new Items();
        $status = false;
        $arrIds = [];
        $objItems->dbTransaction();
        try {
            if (is_array($params['id']) && !empty($params['id'])) {

                if ($params['action'] == '1') {
                    $action = 'Excluded';
                } else if ($params['action'] == '0') {
                    $action = 'Activated';
                }

                $params['is_excluded'] = $params['action'];
                $params['tracking_id'] = $this->unique_id . '-0';
                $arrIds = $params['id'];
                unset($params['action'], $params['parent_item_id'], $params['id'], $params['view_type']);
                $affectedRow = $objItems->whereIn('id', $arrIds)->update($params);
                if (count($arrIds) == $affectedRow) {
                    $status = true;
                }

                /**
                 * Track the activity logs
                 */
                $objLogs = new ActivityLog();
                $logsData = $objLogs->setActivityLog(array('events_id' => $params['events_id'], 'actions' => lcfirst($action), 'tracking_id' => $this->unique_id, 'users_id' => $params['last_modified_by'], 'type' => '0', 'count' => count($arrIds)));
                $objLogs->updateActivityLog($logsData);
                unset($logsData);
            }
            $objItems->dbCommit();
        } catch (\Exception $ex) {
            $objItems->dbRollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            $status = false;
        }

        return ['status' => $status, 'items_id' => $arrIds];
    }

    /**
     * 
     * @param type $command
     * @return type
     */
    function editEventItem($command) {
        ini_set('memory_limit', '250M');
        $params = $command->dataToArray();

        $update = false;
        $is_update = true;
        $objItems = new Items();
        $objEditable = new ItemsEditable();
        $objNonEdit = new ItemsNonEditable();
        $updated_id = $deleted_id = [];
        $grouped_items_id = [];
        $unique_id = $this->unique_id;
        $objItems->dbTransaction();
        try {

            /*
             * Find the Edited cell key exists or not into items column
             */

            $item_key = $params['item_key'];
            $item_key_array = ['searched_item_nbr', 'upc_nbr', 'plu_nbr', 'fineline_number', 'itemsid'];
            if (in_array($item_key, $item_key_array)) {

                $edit_id = $objItems->where('id', $params['item_id'])->first();
                $exist_item_data = $objItems->where('events_id', $params['event_id'])->where($item_key, $params['value'])->first();

                if (empty($exist_item_data) && $edit_id && $edit_id->searched_item_nbr == '' && $edit_id->upc_nbr == '' && $edit_id->plu_nbr == '' && $edit_id->fineline_number == '' && $edit_id->itemsid == '') {
                    $objPriceVersion = new PriceZonesDataSource();
                    $arrResult = $objPriceVersion->getAvailableVersion($params['item_id']);                    
                    /**
                     * Delete the row by item id
                     */
                    $objItems->where('id', $params['item_id'])->delete();
                    $objEditable->where('items_id', $params['item_id'])->delete();
                    $objNonEdit->where('items_id', $params['item_id'])->delete();
                    /**
                     * Adding the new items
                     */
                    $command->items = [$params['value']];
                    $command->search_key = $params['item_key'];
                    $command->event_id = $params['event_id'];
                    $command->userEditable = [];
                    $command->is_price_req = 1;

                    $cmdResponse = $this->saveItems($command);
                    $updated_id = $cmdResponse['items_id'];
                    $deleted_id = array_merge($cmdResponse['deleted_items'], [$params['item_id']]);
                    $update = true;
                    $this->updatePriceVersionToEmptyRow($updated_id, $params['event_id'], $arrResult, $params);
                } else {
                    
                    $objPriceVersion = new PriceZonesDataSource();
                    $arrResult = $objPriceVersion->getAvailableVersion($params['item_id']);                    
                    $objItems->where('id', $params['item_id'])->delete();
                    $objEditable->where('items_id', $params['item_id'])->delete();
                    $objNonEdit->where('items_id', $params['item_id'])->delete();
                    $command->items = [$params['value']];
                    $command->search_key = $params['item_key'];
                    $command->event_id = $params['event_id'];
                    $command->userEditable = [];
                    $command->is_price_req = 2;
                    
                    $cmdResponse = $this->saveItems($command);
                    $updated_id = $cmdResponse['items_id'];
                    $deleted_id = array_merge($cmdResponse['deleted_items'], [$params['item_id']]);
                    $update = true;   
                    $this->updatePriceVersionToEmptyRow($updated_id, $params['event_id'], $arrResult, $params);
                }
            } else {
                if ($params['value'] === 0) {
                    $col[$item_key] = '0';
                } else {
                    $col[$item_key] = $params['value'];
                }

                $edit_id = $objEditable->where('items_id', $params['item_id'])->first();
                $col['id'] = $edit_id->id;
                /**
                 * Apply pricing rules, if user change the price id value
                 */
                if ($item_key == 'price_id' && strtoupper($params['value']) == 'ROLLBACK') {
                    $was_price = is_numeric($edit_id->was_price) ? $edit_id->was_price : 0;
                    $advertised_retail = is_numeric($edit_id->advertised_retail) ? $edit_id->advertised_retail : 0;
                    if ($was_price > 0)
                        $col['save_amount'] = $was_price - $advertised_retail;
                } else if ($item_key == 'price_id') {
                    $col['was_price'] = '';
                } else if ($item_key == 'was_price') {
                    $was_price = is_numeric($col['was_price']) ? $col['was_price'] : 0;
                    $advertised_retail = is_numeric($edit_id->advertised_retail) ? $edit_id->advertised_retail : 0;
                    if ($was_price > 0) {
                        $col['save_amount'] = $was_price - $advertised_retail;
                    } else {
                        $col['save_amount'] = '';
                    }
                } else if ($item_key == 'advertised_retail') {
                    $was_price = is_numeric($edit_id->was_price) ? $edit_id->was_price : 0;
                    $advertised_retail = is_numeric($col['advertised_retail']) ? $col['advertised_retail'] : 0;
                    if ($was_price > 0) {
                        $col['save_amount'] = $was_price - $advertised_retail;
                    }

                    $col['advertised_retail'] = !empty($col['advertised_retail']) ? preg_replace('/[\$,~]/', '', $col['advertised_retail']) : $col['advertised_retail'];
                }

                $params = array_merge($params, $col);
                $params['tracking_id'] = $unique_id . '-0';                
                /**
                 * Unset the insert params value
                 */
                if (isset($params['id']) && !empty($params['id'])) {
                    unset($params['date_added'], $params['gt_date_added'], $params['created_by']);
                }
//                if ($item_key == 'landing_url') {
//                    $exist_item_data_non_edit = $objNonEdit->where('items_id', $params['item_id'])->first();
//                    $objNonEdit->where('id', $exist_item_data_non_edit->id)
//                            ->update(['landing_url' => $params['landing_url'], 'tracking_id' => $unique_id . '-0']);
//                }
//                if ($item_key == 'sbu') {
//                    $exist_item_data_non_edit = $objNonEdit->where('items_id', $params['item_id'])->first();
//                    $objNonEdit->where('id', $exist_item_data_non_edit->id)
//                            ->update(['sbu' => $params['sbu'], 'tracking_id' => $unique_id . '-0']);
//                }
//                if ($item_key == 'acctg_dept_nbr') {
//                    $exist_item_data_non_edit = $objNonEdit->where('items_id', $params['item_id'])->first();
//                    $objNonEdit->where('id', $exist_item_data_non_edit->id)
//                            ->update(['acctg_dept_nbr' => $params['acctg_dept_nbr'], 'tracking_id' => $unique_id . '-0']);
//                }

                if ($item_key == 'attributes') {
                    if (!empty($params['value'])) {
                        $params['attributes'] = json_encode($params['value']);
                    } else {
                        $params['attributes'] = "";
                    }
                }
                /**
                 * Adding leading zero to page 
                 */
                if ($item_key == 'page') {
                    /**
                     * Check given string having any number
                     */
                    $params['page'] = "";
                    $findNumber = preg_replace("/[^0-9]{1,4}/", '', $params['value']);
                    /**
                     * Check input having string along with number or only number, if only number allow to add page value
                     */
                    if (strlen($params['value']) == strlen($findNumber)) {

                        if ($findNumber != 0) {
                            $params['page'] = str_pad($findNumber, 2, '0', STR_PAD_LEFT);
                        }
                    }
                }
                /**
                 * If Edited column is grouped items, add items into existing group
                 */
                if ($item_key == 'grouped_item') {
                    $objGroupDs = new GroupedDataSource();
                    $grouped_items_id = $objGroupDs->addGroupItemsFromEdit($params);
                    $deleted_id = isset($grouped_items_id['items_id']) && !empty($grouped_items_id['items_id']) ? $grouped_items_id['items_id'] : [];
                }

                if (isset($params['id']) && !empty($params['id'])) {
                    $objEditable->saveRecord($params);
                    /**
                     * if edit cell is non-editable columns (It was non-edit now changed to editable)
                     */
                    $this->updateNonEditableField($params);

//                    if ($item_key == 'ad_block' || $item_key == 'page') {
//                        $this->findReplaceVersionByPageOrAdblock($params);
//                    }

                    /**
                     * Update last modified value in Items tbale
                     */
                    $params['id'] = $params['item_id'];
                    $objItems->saveRecord($params);
                    $updated_id = [$edit_id->items_id];
                    $update = true;
                    /**
                     * Track the activity logs
                     */
                    $objLogs = new ActivityLog();
                    $logData = $objLogs->setActivityLog(array('events_id' => $params['event_id'], 'actions' => 'update', 'users_id' => $params['last_modified_by'], 'type' => '0', 'tracking_id' => $this->unique_id, 'count' => count((array) $params['id'])));
                    $objLogs->updateActivityLog($logData);
                    unset($logsData);
                }
            }
            $objItems->dbCommit();
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            $update = false;
            $objItems->dbRollback();
        }
        return ['status' => $update, 'items_id' => $updated_id, 'deleted_items' => $deleted_id, 'is_updated' => $is_update,
            'group_item_id' => isset($grouped_items_id['group_item_id']) ? $grouped_items_id['group_item_id'] : 0];
    }
    /**
     * 
     * @param type $params
     */
    function updateNonEditableField($params) {
        \DB::beginTransaction();
        try {
            $objNoneEdit = new ItemsNonEditable();
            $sql = 'select column_name from information_schema.columns where table_name = \'items_non_editable\'';
            $result = $objNoneEdit->dbSelect($sql);
            $columnArr = [];
            foreach ($result as $row) {
                $columnArr[$row->column_name] = $row->column_name;
            }
            unset($columnArr['id'], $params['id']);

            if (isset($params['item_key']) && !empty($params['item_key'])) {
                if (in_array($params['item_key'], array_values($columnArr))) {
                    $primId = $objNoneEdit->where('items_id', $params['item_id'])->first();
                    $params['id'] = $primId->id;
                    $objNoneEdit->saveRecord($params);
                }
            }
            \DB::commit();
        } catch (Exception $ex) {
            \DB::rollback();
        }
    }
    /**
     * 
     * @param type $intItemID
     * @param type $intEventID
     * @param type $arrData
     */
    function updatePriceVersionToEmptyRow($intItemID, $intEventID, $arrData, $params) {
        $arrPriceId = $arrOmitPriceId = [];
        $intItemID = is_array($intItemID) ? array_shift($intItemID) : '';        
        if ($arrData) {
            if (isset($arrData['available']) && !empty($arrData['available'])) {
                foreach ($arrData['available'] as $row1) {
                    $arrPriceId[] = $row1['id'];
                }
            }
            if (isset($arrData['omit_versions']) && !empty($arrData['omit_versions'])) {
                foreach ($arrData['omit_versions'] as $row2) {
                    $arrOmitPriceId[] = $row2['id'];
                }
            }
            unset($params['item_id'], $params['event_id'], $params['value']);
            $input = array_merge(array('item_id' => $intItemID, 'events_id' => $intEventID, 'value' => $arrPriceId, 'type' => 1, 'omited_versions' => $arrOmitPriceId, 'source' => 'manual'), $params);
            $objPrcZoneDs = new PriceZonesDataSource();
            $versionsCode = $objPrcZoneDs->saveManualVersions($input);
            unset($input, $arrData, $arrPriceId, $arrOmitPriceId);
        }
    }

    /**
     * This method will handle the adding new row into items
     * @param type $command
     * @return type boolean
     */
    function addItemRow($command) {

        $params = $command->dataToArray();        
        /*
         * initiate the object
         */
        $objItems = new Items();
        $objItemEdit = new ItemsEditable();
        $objNonEdit = new ItemsNonEditable();
        $status = false;
        $itemCount = $eventStatus = '';
        $rowID = 0;
        $objItems->dbTransaction();
        try {
            /*
             * Add new row in items
             */
            if (!empty($params['events_id'])) {

                $item_id = $objItems->saveRecord($params);
                $params['items_id'] = $item_id->id;
                $objItemEdit->saveRecord($params);
                $objNonEdit->saveRecord($params);

                $status = true;
                $rowID = $item_id->id;
                /**
                 * if add empty row from grouped items
                 */
                if(isset($params['parent_item_id']) && !empty($params['parent_item_id'])){
                    $objGroup = new ItemsGroups();
                    $intGroupId = $objGroup->where('items_id', $params['parent_item_id'])->first();;
                    $objGroupItem = new ItemsGroupsItems();
                    $params['items_id'] = $rowID;
                    $params['items_groups_id'] = $intGroupId->id;
                    $objGroupItem->saveRecord($params);
                }

                /**
                 * Track the activity logs
                 */
                $objLogs = new ActivityLog();
                $logData = $objLogs->setActivityLog(array('events_id' => $params['events_id'], 'actions' => 'addrow', 'users_id' => $params['last_modified_by'], 'type' => '0', 'tracking_id' => $this->unique_id, 'items_id' => isset($countBy[0]) ? $countBy[0] : []));
                $objLogs->updateActivityLog($logData);
            }
            $objItems->dbCommit();
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            $status = true;
            $objItems->dbRollback();
        }
        return ['status' => $status, 'items_id' => $rowID];
    }

    /**
     * This method will handle the updating item pulish status
     * @param object $command
     * @return Array
     */
    function addItemPublishStatus($command) {
        $i = 0;
        $objItems = new Items();
        $objItems->dbTransaction();
        try {
            $params = $command->dataToArray();
            $arrData = $emptyData = $arrResponse = $dbData = $publishedID = $arrItems = [];
            $itemCount = $eventStatus = '';
//            if (!empty($params['item_id'])) {
//                foreach ($params['item_id'] as $item_id) {
//                    $command->id = $item_id;
//                    $itemList = $this->getItemsGridData($command);
//                    $dbData[] = $this->doArray($itemList['objResult']);
//                }
//            }
            if (!empty($params['item_id'])) {

                $objCopyDs = new CopyItemsDataSource();
                $params['items_id'] = $params['item_id'];
                $itemList = $objCopyDs->getItemListById($params);
                $dbData = $this->doArray($itemList);
            }

            /* Get mandatory field */
            $isMandatory = true;
            $status = false;
            $mndtryCol = [];
            $columns = $this->getItemDefaultHeaders($type = 0);

            foreach ($columns as $key => $value) {
                if ($isMandatory == $value['IsMandatory']) {
                    $mndtryCol[$value['column']] = $value['column'];
                }
            }

            if (!empty($dbData)) {

                /* Intersect with mandatory columns */
                $index = 0;
                foreach ($dbData as $data) {
                    if (!empty($data)) {

                        $uniqValue = array_intersect_key($data, $mndtryCol);
                        $arrData[] = array_merge($uniqValue, ['id' => $data['id']]);
                    }
                    $index++;
                }
                unset($dbData);

                /* Check the mandatory column values, exists or not */
                foreach ($arrData as $val) {
                    foreach ($val as $k => $v) {
                        if (empty($v)) {
                            $emptyData[$val['id']]['id'] = $val['id'];
                            $emptyData[$val['id']]['column'][] = $k;
                        }
                    }
                }

                /* Update publish status, if all the mandatory fields are filled */
                $i = 0;
                if (empty($emptyData)) {

                    $bulkUpdate = "";
                    $params['publish_status'] = true;
                    $objItems = new Items();
                    foreach ($arrData as $id) {
                        $bulkUpdate .= "update items set publish_status = '1', tracking_id = '" . $this->unique_id . "', last_modified_by = '" . $params['last_modified_by'] . "', last_modified = '" . $params['last_modified'] . "',
                                  gt_last_modified = '" . $params['gt_last_modified'] . "', ip_address = '" . $params['ip_address'] . "' where id = " . $id['id'] . ";";
                        $publishedID[] = $id['id'];
                        $i++;
                    }
                    unset($arrData);

                    if (!empty($bulkUpdate)) {
                        $objItems = new Items();
                        $objItems->dbUnprepared($bulkUpdate);
                        $status = true;
                    }
                    $isPublish = $this->isPublishedEvents($params['event_id']);
                    if ($isPublish == 0) {
                        $params['statuses_id'] = 3;
                    } else {
                        $params['statuses_id'] = 2;
                    }

                    /**
                     * Update event status
                     */
                    event(new UpdateEventStatus($params['statuses_id'], $params['event_id']));
                    unset($params['statuses_id']);
                    /**
                     * Track the activity logs
                     */
                    $logsData = array_merge($params, ['events_id' => $params['event_id'], 'actions' => 'publish', 'tracking_id' => $this->unique_id, 'users_id' => $params['last_modified_by'], 'descriptions' => count($publishedID) . ' Items Published']);
                    unset($logsData['id']);
                    event(new ItemsActivityLogs($logsData));
                }
            }
            $objItems->dbCommit();
        } catch (\Exception $ex) {
            $objItems->dbRollback();
        }

        return $arrResponse = ['status' => $status, 'fields' => array_values($emptyData), 'publish_count' => $i, 'published_id' => $publishedID];
    }

    /**
     * Get the items count by item type (items & linked items)
     * @param int $event_id
     * @return array
     */
    function getItemsCount($event_id, $loggedID = 0, $itemsListUserId = 0, $department_id = 0) {
        $permissions = [];
        $departments_id = 0;

        if ($loggedID > 0) {
            $objUsers = new Users;
            $usrObj = $objUsers->where('id', $loggedID)->get(['departments_id']);
            $departments_id = $usrObj[0]->departments_id;
            $permissions = $this->getAccessPermissions($loggedID);
            $permissions['departments_id'] = $departments_id;
        }

        $objItems = new Items();
        $result = $objItems->dbTable('i')
                        ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                        ->selectRaw('CASE items_type WHEN \'0\' THEN \'item\' WHEN \'1\' THEN \'link_item\' END as item_type')
                        ->selectRaw('count(*) as count')
                        ->where('events_id', $event_id)
                        ->where(function($query) use ($permissions, $loggedID) {
                            if ($loggedID > 0) {
                                if (isset($permissions['items_access']) && ($permissions['items_access'] == '6' || $permissions['items_access'] == '4' || $permissions['items_access'] == '5')) {
                                    
                                } else if (isset($permissions['items_access']) && ($permissions['items_access'] == '2' || $permissions['items_access'] == '3')) {
                                    $query->where('u.departments_id', $permissions['departments_id']);
                                } else {
                                    $query->where('i.created_by', $loggedID);
                                }
                            }
                        })->where(function ($query) use ($itemsListUserId) {
                    if (!empty($itemsListUserId)) {
                        $query->where('i.created_by', $itemsListUserId);
                    }
                })->where(function ($query) use ($department_id) {
                    if (!empty($department_id)) {
                        $query->where('u.departments_id', $department_id);
                    }
                })->groupBy('items_type')->get();
        $row = [];
        if (!empty($result)) {
            foreach ($result as $val) {
                $row[$val->item_type] = $val->count;
            }
        }
        unset($permissions);
        return $row;
    }

    /**
     * Convert array
     * @param array $data
     * @return array
     */
    function doArray($data) {
        return collect($data)->map(function($x) {
                    return (array) $x;
                })->toArray();
    }

    /**
     * This method will handle the changing the publish to unpublish item status 
     * @param object $command
     * @return Array
     */
    function makeUnpublishItems($command) {

        $params = $command->dataToArray();
        $status = false;
        $arrIds = [];
        $affectedRow = 0;
        $objItems = new Items();
        $objItems->dbTransaction();

        try {

            if (!empty($params['item_id'])) {
                $arrIds = !empty($params['item_id']) ? $params['item_id'] : [];
                if (!empty($arrIds)) {
                    $updateData = ['publish_status' => '0', 'tracking_id' => $this->unique_id . '-0',
                        'last_modified_by' => $params['last_modified_by'], 'last_modified' => $params['last_modified'],
                        'gt_last_modified' => $params['gt_last_modified'], 'ip_address' => $params['ip_address']
                    ];
                    $affectedRow = $objItems->whereIn('id', $arrIds)->update($updateData);
                    $status = true;
                    unset($updateData);
                }

                /**
                 * Start status update
                 */
                $isPublish = $this->isPublishedEvents($params['events_id']);
                if ($isPublish == 0) {
                    $params['statuses_id'] = 3;
                } else {
                    $params['statuses_id'] = 2;
                }
                /**
                 * Update event status
                 */
                event(new UpdateEventStatus($params['statuses_id'], $params['events_id']));
                unset($params['statuses_id']);

                /**
                 * Track the activity logs
                 */
                $objLogs = new ActivityLog();
                $logsData = $objLogs->setActivityLog(array('events_id' => $params['events_id'], 'actions' => 'unpublish', 'tracking_id' => $this->unique_id, 'users_id' => $params['last_modified_by'], 'type' => '0', 'count' => $affectedRow));
                $objLogs->updateActivityLog($logsData);
                unset($logsData);
            }
            $objItems->dbCommit();
        } catch (\Exception $ex) {
            $objItems->dbRollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            $status = false;
        }

        $arrResponse = ['status' => $status, 'unpublish_count' => $affectedRow, 'unpublish_id' => $arrIds];
        return $arrResponse;
    }

    /**
     * Check given event is having published items or not
     * @param int $eventID
     * @return int
     */
    function isPublishedEvents($eventID) {

        $objItems = new Items();
        $publishCount = $objItems->where('events_id', $eventID)
                        ->where('publish_status', '0')
                        ->where('items_type', '0')->count();
        return $publishCount;
    }

    function getRandomUsers() {
        $objUsers = new Users();
        $objResult = $objUsers->where('status', 1)
                ->selectRaw('firstname')
                ->inRandomOrder()
                ->limit(5)
                ->get();
        return $objResult;
    }

    /**
     * Common function for get the permissions
     * 
     * @param int $loggedID
     * @return array
     */
    function getAccessPermissions($loggedID) {

        $objCommand = new GetPermissions(['users_id' => $loggedID]);
        $permissions = CommandFactory::getCommand($objCommand);
        $data = [];
        foreach ($permissions['system_permissions'] as $row) {
            $data[$row->code] = $row->permission[0];
        }

        return $data;
    }

    /**
     * Get Master dropdown values from master data option table
     * 
     * @param int $moduleId
     * @return array
     */
    function getMasterDataOptions($moduleId = 0) {
        $dropValue = [];
        $objMasterOptions = new MasterDataOptions();
        $dbResult = $objMasterOptions->where('module_id', $moduleId)->get(['id', 'name', 'module_id'])->toArray();
        if (!empty($dbResult)) {
            foreach ($dbResult as $options) {
                $dropValue[$options['module_id']][$options['id']] = $options['name'];
            }
        }
        return array_shift($dropValue);
    }

    /**
     * Get Historical Cross Data by Selected Item Number
     * @param Object $command
     * @return Object
     */
    function getHistoricalCrossData($command) {

        $params = $command->dataToArray();
        $objItems = new Items();
        $objResult = [];
        $item_row = $objItems->where('id', $params['item_nbr'])->get(['searched_item_nbr'])->toArray();
        if (isset($item_row[0]) && isset($item_row[0]['searched_item_nbr']) && !empty($item_row[0]['searched_item_nbr'])) {
            $objResult = $objItems->dbTable('i')
                            ->join('events as e', 'e.id', '=', 'i.events_id')
                            ->select('i.searched_item_nbr', 'i.events_id', 'e.name AS event_name', 'e.start_date', 'e.end_date')
                            ->where('e.is_draft', '0')
                            ->where(function ($query) use ($item_row) {
                                $query->where('i.searched_item_nbr', $item_row[0]['searched_item_nbr'])->where('i.items_type', '0');
                            })->groupBy('i.events_id');
            /**
             * Sorting
             */
            if (isset($params['order']) && (isset($params['sort']) && !empty($params['sort']))) {
                $objResult = $objResult->orderBy($params['order'], $params['sort']);
            } else {
                $dbResult = $objResult->orderBy('e.start_date', 'desc');
            }
            /**
             * Pagination
             */
            if (isset($params['page']) && !empty($params['page'])) {
                $objResult = $objResult->paginate($params['perPage']);
            } else {
                $objResult = $objResult->get();
            }
        }
        return $objResult;
    }

    /**
     * Update the multiple rows
     * @param object $command
     * @return array
     */
    function editMultipleItems($command) {
        $params = $command->dataToArray();
        $params['tracking_id'] = $this->unique_id;
        $status = false;
        $objItems = new Items();
        $objEdit = new ItemsEditable();
        $objNonEdit = new ItemsNonEditable();
        $postData = $updated_id = [];
        $objItems->dbTransaction();
        $duplicateVersions = $existsVersions = $masterItemEvent = [];

        try {
            $post = [];
            if (isset($params['list']) && !empty($params['list'])) {
                foreach ($params['list'] as $key => $input) {
                    $post[$input['item_id']]['id'] = $input['item_id'];
                    $post[$input['item_id']][$input['item_key']] = $input['value'];
                }
            }

            if (!empty($post)) {
                foreach ($post as $id => $values) {

                    unset($params['list']);
                    if (!empty($update['id'])) {
                        unset($params['created_by'], $params['date_added'], $params['gt_date_added']);
                    }
                    /**
                     * Adding leading zero to page
                     */
                    if (isset($values['page'])) {
                        $page = "";
                        $findNumber = preg_replace("/[^0-9]{1,4}/", '', $values['page']);
                        if (strlen($values['page']) == strlen($findNumber)) {
                            if ($findNumber != 0) {
                                $page = str_pad($findNumber, 2, '0', STR_PAD_LEFT);
                            }
                        }
                        $values['page'] = $page;
                    }

                    if (isset($params['item_key']) && $params['item_key'] == 'versions') {
                        $obj = new PriceZonesDataSource();
                        $response = $obj->copyMultiplePriceZones($values, $params['events_id'], $log = false, $params['tracking_id']);                        
                        $updated_id[] = isset($response['items_id']) ? $response['items_id'] : 0;
                        $duplicateVersions[] = isset($response['duplicate']) && !empty($response['duplicate']) ? $response['duplicate'] : [];
                        $existsVersions[] = isset($response['exists']) && !empty($response['exists']) ? $response['exists'] : [];
                    } else if (isset($params['item_key']) && $params['item_key'] == 'mixed_column2') {
                        $obj = new PriceZonesDataSource();
                        $response = $obj->copyMultipleOmitPriceZones($values, $params['events_id'], $log = false, $params['tracking_id']);                        
                        $updated_id[] = isset($response['items_id']) ? $response['items_id'] : 0;
                        $duplicateVersions[] = isset($response['duplicate']) && !empty($response['duplicate']) ? $response['duplicate'] : [];
                        $existsVersions[] = isset($response['exists']) && !empty($response['exists']) ? $response['exists'] : [];
                    } else {

                        $postData = array_merge($params, $values);
                        $objItems->saveRecord($postData);

                        $editPrimId = $objEdit->where('items_id', $values['id'])->first();
                        $postData['id'] = $editPrimId->id;
                        $objEdit->saveRecord($postData);

                        $noneditPrimId = $objNonEdit->where('items_id', $values['id'])->first();
                        $postData['id'] = $noneditPrimId->id;
                        $objNonEdit->saveRecord($postData);
                        $updated_id[] = $values['id'];
                    }
                }

                unset($postData);
                $status = true;
                /**
                 * Track the activity logs
                 */
                if ($status == true) {
                    $logsData = array_merge($params, ['events_id' => $params['events_id'], 'actions' => 'update', 'users_id' => $params['last_modified_by'], 'descriptions' => count(array_unique($updated_id)) . ' Items Updated']);
                    unset($logsData['id']);
                    event(new ItemsActivityLogs($logsData));
                    unset($logsData);
                }
            }

            unset($post);
            $objItems->dbCommit();
        } catch (\Exception $ex) {
            $objItems->dbRollback();
        }
        return ['status' => $status, 'items_id' => $updated_id, 'duplicate' => array_filter($duplicateVersions), 'exists' => array_filter($existsVersions)];
    }

    /**
     * Get Events status in items list by users permissions
     * @param type $command
     * @return string
     */
    function getItemListStatusByUsers($command) {
        $params = $command->dataToArray();
        $permissions = [];
        $departments_id = 0;
        $users_id = (isset($params['users_id']) && $params['users_id'] != 0) ? $params['users_id'] : $params['last_modified_by'];

        $objUsers = new Users;
        $usrObj = $objUsers->where('id', $users_id)->get(['departments_id']);
        $departments_id = $usrObj[0]->departments_id;
        if ($users_id > 0) {
            $permissions = $this->getAccessPermissions($users_id);
            $permissions['departments_id'] = $departments_id;
        }

        $objItems = new Items();
        $result = $objItems->dbTable('i')
                        ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                        ->selectRaw('count(*) as itemCount')
                        ->selectRaw('sum(if(i.publish_status = "0", 1, 0)) as unPublishedCount')
                        ->where('events_id', $params['event_id'])
                        ->where(function($query) use ($permissions, $params) {
                            if ($params['last_modified_by'] > 0) {
                                if (isset($permissions['items_access']) && ($permissions['items_access'] == '6' || $permissions['items_access'] == '4' || $permissions['items_access'] == '5')) {
                                    
                                } else if (isset($permissions['items_access']) && ($permissions['items_access'] == '2' || $permissions['items_access'] == '3')) {
                                    $query->where('u.departments_id', $permissions['departments_id']);
                                } else {
                                    $query->where('i.created_by', $params['last_modified_by']);
                                }
                            }
                        })->where('items_type', '0')->get();

        $row = '';
        if (!empty($result)) {
            foreach ($result as $val) {
                /**
                 * Check item count and Unpublished count
                 */
                if ($val->itemCount > 0 && $val->unPublishedCount == 0) {
                    $row = 'PUBLISHED';
                } else if ($val->itemCount > 0 && $val->unPublishedCount > 0) {
                    $row = 'ACTIVE';
                } else if ($val->itemCount == 0) {
                    $row = 'NEW';
                }
            }
        }
        return $row;
    }

    /**
     * Find the table alaise name given by sorting column in Items grid
     * @param string $column
     * @return string
     */
    function findTableAliaseName($column) {

        $getColumns = $this->getItemDefaultHeaders($linked_item_type = 0);
        $columnAliase['aliase'] = "i";
        foreach ($getColumns as $row) {
            if ($column == $row['column']) {
                $columnAliase['aliase'] = $row['aliases_name'];
                $columnAliase['type'] = $row['type'];
            }
        }
        return $columnAliase;
    }

    /**
     * This is method will used for OneOps to get the linked items
     * @param type $master_id
     * @return MasterItems
     */
    function getLinkedItemsFromMasterItems($master_id, $item_number) {
        $itemsArray = [];
        if (!empty($master_id)) {
            $objMaster = new MasterItems();
            $objMaster = $objMaster->where('parent_id', $master_id)->get()->toArray();

            if (!empty($objMaster)) {
                foreach ($objMaster as $row) {
                    unset($row['id'], $row['parent_id']);
                    if ($item_number != $row['searched_item_nbr']) {
                        $itemsArray[] = $row;
                    }
                }
            }
        }

        return $itemsArray;
    }

    /**
     * 
     * @param array $array
     * @param array $commonData
     * @return array
     */
    function saveLinkedItemsFromMasterItems(array $array, $commonData) {
        \DB::beginTransaction();
        try {
            $objItems = new Items();
            $objEdit = new ItemsEditable();
            $objNonEdit = new ItemsNonEditable();
            $saveLinkID = [];
            if (is_array($array)) {

                $arrayItem = $this->getLinkedItemsFromMasterItems($array['master_items_id'], $array['itemNbr']);
                $primData['versions'] = 'No Price Zone available.';
                $primData['link_item_parent_id'] = 0; //isset($array['items_id']) ? $array['items_id'] : "";
                $primData['events_id'] = isset($array['events_id']) ? $array['events_id'] : '';
                $primData['items_type'] = '1';
                $primData['upc_nbr'] = isset($array['upc_nbr']) ? $array['upc_nbr'] : '';
                $primData['master_items_id'] = isset($array['master_items_id']) ? $array['master_items_id'] : '';

                if (!empty($arrayItem)) {
                    $existLinkedItems = $this->getItemsLikedItemsByEvent($primData['events_id'], $primData['upc_nbr']);
                    foreach ($arrayItem as $rowValue) {
                        $itemmd5 = md5($rowValue['upc_nbr'] . '_' . $rowValue['searched_item_nbr']);
                        if (!in_array($itemmd5, $existLinkedItems)) {
                            $arrSaveData = array_merge(array_merge($primData, $rowValue), $commonData);
                            $arrSaveData['tracking_id'] = $this->unique_id . '-1';
                            $saveID = $objItems->saveRecord($arrSaveData);
                            $arrSaveData['items_id'] = $saveID->id;
                            $objEdit->saveRecord($arrSaveData);
                            $objNonEdit->saveRecord($arrSaveData);
                            $saveLinkID[] = $saveID->id;
                            $existLinkedItems[] = $itemmd5;
                        }
                    }
                }
            }
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }

        return $saveLinkID;
    }

    /**
     * 
     * @param type $event_id
     * @param type $upc_nbr
     * @return type
     */
    function getItemsLikedItemsByEvent($event_id, $upc_nbr) {
        $itemsArray = [];
        if (!empty($event_id) && !empty($upc_nbr)) {
            $objItems = new Items();
            $objMaster = $objItems->where('events_id', $event_id)
                            ->where('upc_nbr', $upc_nbr)//->where('items_type', '1')
                            ->get(['upc_nbr', 'searched_item_nbr'])->toArray();

            if (!empty($objMaster)) {
                foreach ($objMaster as $row) {
                    $md5 = md5($row['upc_nbr'] . '_' . $row['searched_item_nbr']);
                    $itemsArray[] = $md5;
                }
            }
        }

        return $itemsArray;
    }

    /**
     * In this method will handle the activity logs for delte exists item while add items, if same items id already exists in
     * the evnets
     * @param type $command
     * @param array $commonData
     */
    function addActivityLogDeleExistsItems($command, array $commonData) {
        $params = $command->dataToArray();
        $deleted_items = $masterItemsId = [];
        $masterData = $this->getMasterItemsByVersion($command);
        $linkItmCnt = 0;
        foreach ($masterData as $values) {
            foreach ($values as $masterValues) {
                $masterItemsId[] = $masterValues['id'];
            }
        }

        $itemsid = $masterItemsId;
        $objItems = new Items();
        $result = $objItems->whereIn('master_items_id', $itemsid)
                ->where('events_id', $params['event_id'])
                ->where('items_type', '0')
                ->get(['id'])
                ->toArray();
        unset($masterItemsId);
        if (!empty($result)) {
            foreach ($result as $row) {
                $deleted_items[] = $row['id'];
            }

            /**
             * get count of deleted items and linked items
             */
            $itemsCount = $objItems->select('items_type')
                    ->selectRaw('count(*) as cnt')
                    ->whereIn('master_items_id', $itemsid)
                    ->where('events_id', $params['event_id'])
                    ->groupby('items_type')
                    ->get(['items_type', 'cnt'])
                    ->toArray();

            if ($itemsCount) {
                foreach ($itemsCount as $count) {
                    $countBy[$count['items_type']] = $count['cnt'];
                }
            }
            $this->deleteExistsItems($params['event_id'], $itemsid);
            /**
             * Deleted items related channels
             */
            $objChannelDs = new ChannelsDataSource();
            $objChannelDs->deleteChannelsItemsAdTypes($deleted_items);
            /**
             * Track the activity logs
             */
            $itemCnt = isset($countBy['0']) ? $countBy['0'] : 0;
            $linkItmCnt = isset($countBy['1']) ? $countBy['1'] : 0;
            $logsData = array_merge($commonData, ['events_id' => $params['event_id'], 'actions' => 'delete', 'users_id' => $params['last_modified_by'], 'descriptions' => $itemCnt . ' Items Updated']);
            unset($logsData['id']);
            if ($itemCnt > 0) {
                $logsData['tracking_id'] = $this->unique_id . '-0';
                event(new ItemsActivityLogs($logsData));
                event(new UpdateLogDeleteItems($countBy[0], $logsData['tracking_id']));
            }
            if ($linkItmCnt > 0) {
                $logsData['descriptions'] = $linkItmCnt . ' Linked Items Updated';
                $logsData['type'] = '1';
                $logsData['tracking_id'] = $this->unique_id . '-1';
                event(new ItemsActivityLogs($logsData));
                event(new UpdateLogDeleteItems($countBy[1], $logsData['tracking_id']));
            }
//            $objLogs = new ActivityLog();                
//            $logData = $objLogs->setActivityLog(array('events_id' => $params['event_id'], 'actions' => 'delete', 'users_id' => $params['last_modified_by'], 'count' => $itemCnt, 'type' => '0', 'tracking_id' => $this->unique_id, 'items_id' => isset($countBy[0]) ?$countBy[0] : []));
//            $logData = $objLogs->setActivityLog(array('events_id' => $params['event_id'], 'actions' => 'delete', 'users_id' => $params['last_modified_by'], 'count' => $linkItmCnt, 'type' => '1', 'tracking_id' => $this->unique_id, 'items_id' => isset($countBy[1]) ? $countBy[1] : []));
//            $objLogs->updateActivityLog($logData);
            unset($logsData, $itemsid);
        }

        return ['deleteItems' => $deleted_items, 'deleteLinkItems' => $linkItmCnt];
    }

    /**
     * Get Attributes columns values
     * @param int $params
     * @return array
     */
    function getAttributeColumnValues($params) {

        $masterValues = $this->getMasterDataOptions(4);
        $rowID = isset($params['items_id']) ? $params['items_id'] : 0;
        $objItemEdit = new ItemsEditable();
        $dbResult = $objItemEdit->where('items_id', $rowID)->get(['attributes'])->toArray();
        $dbValues = [];
        $arrayValues['attributes']['selectedValues'] = [];
        $arrayValues['attributes']['available'] = [];
        if (isset($dbResult[0]) && isset($dbResult[0]['attributes']) && !empty($dbResult)) {
            foreach ($dbResult as $row) {
                if (!empty($row['attributes'])) {
                    $dbValues = \GuzzleHttp\json_decode($row['attributes']);
                    $dbValues = array_flip($dbValues);
                }
            }
        }
        if (!empty($dbValues)) {
            foreach ($dbValues as $key => $values) {
                if (isset($masterValues[$key])) {
                    $arrayValues['attributes']['selectedValues'][] = $key;
                }
            }
        }
        foreach ($masterValues as $id => $val) {
            $arrayValues['attributes']['available'][] = ['id' => $id, 'name' => $val];
        }

        return $arrayValues;
    }

    /**
     * Update the colour codes for selected columns
     * @param array $params
     * @return array
     */
    function updateColourCodesByItems($params) {
        DB::beginTransaction();
        $status = false;
        $dbColors = $inputColors = $itemsId = $arrItemsID = [];
        try {
            if (isset($params['color_code']) && !empty($params['color_code'])) {
                $colorArray = isset($params['color_code']) ? $params['color_code'] : [];
                foreach ($colorArray as $value) {
                    $arrItemsID[] = $value['id'];
                    foreach ($value['columns'] as $column) {
                        $inputColors[$value['id']][$column] = $params['color_id'];
                    }
                }

                $objItems = new Items();
                $dbQuery = $objItems->whereIn('id', $arrItemsID)->get(['cell_color_codes', 'id'])->toArray();
                foreach ($dbQuery as $row) {
                    $dbColors[$row['id']] = (array) json_decode($row['cell_color_codes']);
                }
                unset($arrItemsID);

                //$tableColumns = $this->getFillableColumns();
                $sqlUpdate = '';
                foreach ($inputColors as $key => $value) {
                    $arrayData = array_merge(isset($dbColors[$key]) ? $dbColors[$key] : [], $value);
                    foreach ($arrayData as $k => $val) {
                        if ($val == 0) {
                            unset($arrayData[$k]);
                        }
//                        if (!in_array($k, $tableColumns)) {                            
//                            unset($arrayData[$k]);
//                        }
                    }
                    $data['id'] = $key;
                    $data['cell_color_codes'] = !empty($arrayData) ? json_encode($arrayData) : '';
                    $sqlUpdate.="UPDATE items SET cell_color_codes = '" . $data['cell_color_codes'] . "', "
                            . "last_modified_by = " . $params['last_modified_by'] . ", last_modified = '" . $params['last_modified'] . "', "
                            . "ip_address = '" . $params['ip_address'] . "', gt_last_modified = '" . $params['gt_last_modified'] . "' "
                            . "WHERE id = " . $data['id'] . "; ";
                    $itemsId[] = $data['id'];
                    unset($arrayData);
                }

                if (!empty($sqlUpdate)) {
                    $objItems->dbUnprepared($sqlUpdate);
                }
                unset($dbColors, $inputColors);
                $status = true;
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            throw new DataValidationException($ex->getMessage(), new MessageBag());
        }

        return ['status' => $status, 'items_id' => $itemsId];
    }

    /**
     * Get the Items, ItemsEditable, ItemsNOnEditable table columns
     * @return Array
     */
    function getFillableColumns() {
        $objItems = new Items();
        $itemAttributes = $objItems->getFillable();
        $objEdit = new ItemsEditable();
        $editAttributes = $objEdit->getFillable();
        $objNonEdit = new ItemsNonEditable();
        $nonEditAttributes = $objNonEdit->getFillable();
        $tableColumns = array_merge(array_merge($itemAttributes, $editAttributes), $nonEditAttributes);
        return $tableColumns;
    }

    /**
     * Get Items Attributes selected values
     * @param type $values
     * @return string
     */
    function getAttributesSelectedValues($values) {
        $attributes = NULL;
        if (!empty($values)) {
            $result = $this->getMasterDataOptions(4);
            $jsonDecode = \GuzzleHttp\json_decode($values);
            if (is_array($jsonDecode)) {
                foreach ($jsonDecode as $val) {
                    $attributes[] = isset($result[$val]) ? $result[$val] : '';
                }
                $attributes = implode(", ", $attributes);
            }
        }
        return $attributes;
    }

    /**
     * 
     * @param type $row_id
     * @param type $eventid
     * @return type
     */
    function isMovable($row_id = 0, $eventid) {

        $arrUpc = $arrValues = $final = $uniqueUpc = [];
        $isMovable = false;
        $objItems = new Items();
        $objResult = $objItems->dbTable('i')
                        ->select('i.upc_nbr')->whereIn('i.id', $row_id)
                        ->where('is_no_record', '0')->where('upc_nbr', '!=', '')->get()->toArray();
        foreach ($objResult as $row) {
            $arrUpc[] = $row->upc_nbr;
        }

        if (!empty($objResult)) {
            $sql = 'SELECT COUNT(upc_nbr) AS cnt, i.upc_nbr as upc, i.searched_item_nbr, i.id
                    FROM items AS i USE INDEX (idx_upc_type_event_id)
                    WHERE i.items_type = "0" AND i.events_id = ' . $eventid . ' 
                    AND i.upc_nbr in(' . '\'' . implode("','", $arrUpc) . '\'' . ')
                    GROUP BY i.upc_nbr,i.searched_item_nbr
                    HAVING cnt = 1  ';
            $result = $objItems->dbSelect($sql);
            $arrResult = $this->doArray($result);
            unset($arrUpc);

            foreach ($arrResult as $v) {
                if (isset($uniqueUpc[$v['upc']]) && isset($uniqueUpc[$v['upc']][$v['searched_item_nbr']])) {
                    unset($final[$v['upc']]);
                } else {
                    $uniqueUpc[$v['upc']][$v['searched_item_nbr']] = $v['id'];
                    $final[$v['upc']][] = $v['id'];
                }
            }
            unset($uniqueUpc);
        }
        foreach ($final as $upc => $arrid) {
            if (count($arrid) == 1) {
                unset($final[$upc]);
            }
        }

        if (!empty($final)) {
            foreach ($final as $arrItemsid) {
                foreach ($arrItemsid as $val) {
                    $arrValues[] = $val;
                }
            }
            unset($final);
        }
        return $arrValues;
    }

    /**
     * 
     * @param type $data
     * @return type
     */
    function addUsersToChannels($data) {
        \DB::beginTransaction();
        $save = [];
        try {

            $objEventLiveUser = new EventsLiveUsers();
            $data['events_id'] = PiLib::piDecrypt($data['event_id']);
            /**
             * Delete if already token exists
             */
            $checkResult = $objEventLiveUser->where('users_token', $data['users_token'])->delete();
            $objEventLiveUser->saveRecord($data);
            $save['items'] = $this->getBroadcastLiveUsers($data['events_id']);

            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
        return $save;
    }

    /**
     * 
     * @param type $data
     * @return type
     */
    function removeUsersFromChannels($data) {
        \DB::beginTransaction();
        $delete = [];
        try {
            $objEventLiveUser = new EventsLiveUsers();
            $delete = $objEventLiveUser->where('users_token', $data['users_token'])->delete();
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
        return $delete;
    }

    function getBroadcastLiveUsers($intEventID) {
        $arrUsers = [];
        try {
            $objEventLiveUser = new EventsLiveUsers();
            $result = $objEventLiveUser->dbTable('el')
                    ->join('users as u', 'u.id', '=', 'el.users_id')
                    ->where('events_id', $intEventID)
                    ->groupBy('u.id')
                    ->orderBy('el.id', 'asc')
                    ->get()
                    ->toArray();

            if (!empty($result)) {
                foreach ($result as $users) {
                    $users->profile_name = strtoupper(substr($users->firstname, 0, 1) . substr($users->lastname, 0, 1));
                    $ext = pathinfo($users->profile_image_url, PATHINFO_EXTENSION);
                    if (empty($ext)) {
                        $profile_image = null;
                    } else {
                        $fileInfo = pathinfo($users->profile_image_url);
                        $profile_image = URL::to($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '_small.' . $fileInfo['extension']);
                    }
                    $users->profile_image_url = $profile_image;
                    $arrUsers['liveUsers'][] = (array) $users;
                }
            }
        } catch (\Exception $ex) {
            $exMsg = $ex->getMessage() . $ex->getFile() . $ex->getLine();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }

        return $arrUsers;
    }

    /**
     * 
     * @param type $params
     * @return type
     */
    function addUserToEditChannels($params) {
        \DB::beginTransaction();
        $save = [];
        try {
            $params['events_id'] = PiLib::piDecrypt($params['event_id']);
            $params['gt_date_added'] = gmdate('Y-m-d H:i:s');
            $objEventsLiveUsersEdit = new EventsLiveUsersEdit();
            $objEventsLiveUsersEdit->saveRecord($params);
            $save['items'] = $this->getUsersLiveEditInfo($params['events_id']);
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
            $exMsg = $ex->getMessage() . $ex->getFile() . $ex->getLine();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $save;
    }

    /**
     * 
     * @param type $intEventID
     * @return type
     */
    function getUsersLiveEditInfo($intEventID) {
        $arrInfo = [];
        $objUserEdit = new EventsLiveUsersEdit();
        $result = $objUserEdit->dbTable('elue')
                ->join('users as u', 'u.id', '=', 'elue.users_id')
                ->select('u.color_code', 'elue.items_id', 'u.id', 'u.firstname', 'u.lastname', 'u.email', 'u.profile_image_url')
                ->selectRaw('concat(u.firstname, \' \', u.lastname) as name')
                ->where('elue.events_id', $intEventID)
                ->groupBy('u.id')
                ->orderBy('elue.id', 'asc')
                ->get();
        if (!empty($result)) {
            foreach ($result as $users) {
                $users->profile_name = strtoupper(substr($users->firstname, 0, 1) . substr($users->lastname, 0, 1));
                $ext = pathinfo($users->profile_image_url, PATHINFO_EXTENSION);
                if (empty($ext)) {
                    $profile_image = null;
                } else {
                    $fileInfo = pathinfo($users->profile_image_url);
                    $profile_image = URL::to($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '_small.' . $fileInfo['extension']);
                }
                $users->profile_image_url = $profile_image;
                $arrInfo['accessUser'][] = (array) $users;
            }
        }
        return $arrInfo;
    }

    /**
     * 
     * @param type $params
     * @return type
     */
    function removeUserFromEditChannels($params) {

        \DB::beginTransaction();
        $delete = [];
        try {
            $objEventsLiveUsersEdit = new EventsLiveUsersEdit();
            $delete = $objEventsLiveUsersEdit->where('users_token', $params['users_token'])->delete();
            \DB::commit();
        } catch (\Exception $ex) {
            $exMsg = $ex->getMessage() . $ex->getFile() . $ex->getLine();
            \DB::rollback();
        }
        return $delete;
    }

    function getGroupNameByEventId($intEventId) {
        $groups = [];
        $objItemsGroup = new ItemsGroups();
        $result = $objItemsGroup
                        ->where('events_id', $intEventId)->orderBy('name', 'asc')->get()->toArray();
        if (!empty($result)) {

            foreach ($result as $row) {
                $groups[] = $row['name'];
            }
        }
        return $groups;
    }

    /**
     * 
     * @param type $input
     * @throws DataValidationException
     */
    function findReplaceVersionByPageOrAdblock($input) {

        DB::beginTransaction();
        try {
            $arrIntPriceId = [];

            if (isset($input['item_id']) && !empty($input['item_id'])) {
                $objPriceDs = new PriceZonesDataSource();
                $availableVers = $objPriceDs->getAvailableVersion($input['item_id'], array());

                if (isset($availableVers['available']) && !empty($availableVers['available'])) {
                    foreach ($availableVers['available'] as $row) {
                        $arrIntPriceId[] = $row['id'];
                    }
                }

                $params = ['item_id' => $input['item_id'], 'events_id' => $input['event_id'], 'value' => $arrIntPriceId];
                unset($arrIntPriceId);
                $result = $objPriceDs->checkPageAdBlock($params);

                if (isset($result['isNotExists']) && empty($result['isNotExists'])) {
                    $objItemPriceZone = new ItemsPriceZones();
                    $affected = $objItemPriceZone->where('items_id', $input['item_id'])->where('is_omit', '0')->delete();
                    $objEdit = new ItemsEditable();
                    $objEdit->where('items_id', $input['item_id'])->update(['versions' => 'No Price Zone found.']);
                }
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            throw new DataValidationException($exMsg, new MessageBag());
        }
    }

    /**
     * Vendor Suplly Dropdown values
     * @return type
     */
    function getVendorSupplyOptions() {
        $array = [];
        $option = $this->getMasterDataOptions(9);
        foreach ($option as $key => $value) {
            $array['option'][] = array('id' => $key, 'value' => $value);
        }
        $optionValue = $this->getMasterDataOptions(10);
        foreach ($optionValue as $subKey => $subValue) {
            $array['optionValue'][] = array('id' => $subKey, 'name' => $subValue);
        }

        return $array;
    }

    /**
     * Get Selected and Deafult Vendor Supply values
     * @param int $intItemsID
     * @return array
     */
    function getVendorSupplyValue($intItemsID) {
        $array['selectedOption'] = $array['selectedOptionVal'] = [];
        if (isset($intItemsID['items_id']) && !empty($intItemsID['items_id'])) {
            $objItemsEdit = new ItemsEditable();
            $supplyOpt = $objItemsEdit->where('items_id', $intItemsID['items_id'])->first();

            $strExplode = explode(':', $supplyOpt->local_sources);

            $array['selectedOption'] = isset($strExplode[0]) && !empty($strExplode[0]) ? (int) $strExplode[0] : config('smartforms.vendorSupplyOption'); //default yes
            $array['selectedOptionVal'] = isset($strExplode[1]) && !empty($strExplode[1]) ? (int) $strExplode[1] : null;
        }

        return $array;
    }

}
