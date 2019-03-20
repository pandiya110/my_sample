<?php

namespace CodePi\Events\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Events;
use CodePi\Base\Eloquent\Users;
use CodePi\Events\DataSource\DataSourceInterface\iEvents;
use CodePi\Base\Eloquent\Status;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Eloquent\Items;
use Auth,
    Session;
use App\User;
use App\Events\UpdateEventStatus;
use DB;
use CodePi\Base\Eloquent\ItemsEditable;
use CodePi\Base\Eloquent\EventsUsers;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
use App\Events\UpdateEventsDependency;
use CodePi\ItemsActivityLog\Logs\ActivityLog;

/**
 * Handle the Events save ,listing and search
 */
class EventsDataSource implements iEvents {
    
    private $unique_id;
    
    function __construct() {
        $this->unique_id = mt_rand() . time();
    }

    /**
     * Add and Update the Events informations
     * 
     * @params int $command->id
     * @params string $command->name
     * @params string $command->start_date
     * @params string $command->end_date
     * @params string $command->is_draft
     * @return Events Objects
     */    
    function saveEvents($command) {

        $params = $command->dataToArray();
        $objEvents = new Events();
        $saveDetails = [];
        $objEvents->dbTransaction();
        $findRecord = [];
        try {
            if (!empty($params['id'])) {
                $findRecord = $objEvents->findRecord($params['id']);
            }
            $saveDetails = $objEvents->saveRecord($params);
            event(new UpdateEventsDependency($findRecord, $params));
            $objEvents->dbCommit();
        } catch (\Exception $ex) {
            $objEvents->dbRollback();
            throw new DataValidationException($ex->getMessage(), new MessageBag());
        }
        return $saveDetails;
    }

    /**
     * Get the list of Events
     * 
     * @param int $command->id
     * @param string $command->is_draft 
     * @param Find the specific date range records $command->start_date & $command->end_date 
     * @param $command->status_id , filter by status value
     * @param Find the data based on search values (Events name or Status name) $command->search 
     * @return Events List object
     * 
     */
    function getEvents($command) {
        try {
            $departments_id = 0;
            if (\Auth::check()) {
                $departments_id = \Auth::user()->departments_id;
            }
            $params = $command->dataToArray();
            $objEvents = new Events();

            $objItemsDS = new ItemsDataSource();
            $permissions = $objItemsDS->getAccessPermissions($params['last_modified_by']);
            $permissions['departments_id'] = $departments_id;

            $sql = "select * from (select *, ifnull(cnt,0) as item_count, ifnull(unpublish_cnt, 0) as unPublishedCount,
                if(is_draft = '0' and statusid!= 5, CASE 
                    when (ifnull(cnt,0) >0 and ifnull(unpublish_cnt, 0) > 0) then 'ACTIVE' 
                    when (ifnull(cnt,0)  >0 and ifnull(unpublish_cnt, 0) = 0) then 'PUBLISHED'
                    when (ifnull(cnt,0)  = 0 and ifnull(unpublish_cnt, 0) = 0) then 'NEW'
                    end, statusname
                  ) AS status,
                if(is_draft = '0' and statusid!=5, case 
                    when (ifnull(cnt,0) >0 and ifnull(unpublish_cnt, 0) > 0) then 2 
                    when (ifnull(cnt,0)  >0 and ifnull(unpublish_cnt, 0) = 0) then 3
                    when (ifnull(cnt,0)  = 0 and ifnull(unpublish_cnt, 0) = 0) then 1
                    end, statusid
                  ) AS status_id 
                from (
                select
                e.id,e.name as event_name, s.id as statusid, s.name as statusname, e.start_date, e.end_date, 
                e.last_modified, e.is_draft, c.name as campaigns_name, c.id as campaigns_id, c.aprimo_campaign_id, 
                e.access_type, e.created_by, group_concat( distinct eu.users_id) as events_users_id,
                cp.id as campaigns_projects_id, cp.title as project_name, cp.aprimo_project_id
                from events AS e
                left join campaigns as c on c.id = e.campaigns_id
                left join campaigns_projects as cp on cp.id = e.campaigns_projects_id
                left join statuses as s on s.id = e.statuses_id
                left join events_users as eu on eu.events_id = e.id";
            /**
             * Add global or draft events differenciate
             */
            if (isset($params['is_draft']) && $params['is_draft'] >= '0') {
                if ($params['is_draft'] == '0') {
                    $sql .= " where e.is_draft= '" . $params['is_draft'] . "' ";
                } else if ($params['is_draft'] == '1') {
                    $sql .= " where(e.is_draft = '" . $params['is_draft'] . "' and e.created_by = " . $params['last_modified_by'] . ")";
                }
            } else {
                $sql .= " where e.is_draft = '0' or (e.is_draft = '1' and e.created_by = " . $params['last_modified_by'] . ")";
            }
            if (isset($params['id']) && !empty($params['id'])) {
                $sql .= " and e.id = " . $params['id'] . "";
            }
            $sql .= " group by e.id) as a
                left join (
                select count(i.events_id) as cnt, i.events_id, sum(if(i.publish_status = '0', 1, 0)) as unpublish_cnt
                from items as i
                left join users as u on u.id = i.created_by
                where i.items_type ='0' ";
            /**
             * Add permissions
             */
            if (isset($permissions['items_access']) && ($permissions['items_access'] == '6' || $permissions['items_access'] == '4' || $permissions['items_access'] == '5')) {
                
            } else if (isset($permissions['items_access']) && ($permissions['items_access'] == '2' || $permissions['items_access'] == '3')) {
                $sql .= " and u.departments_id = " . $permissions['departments_id'] . "";
            } else {
                $sql .= " and i.created_by = " . $params['last_modified_by'] . "";
            }

            $sql .= " group by i.events_id ) as b on a.id = b.events_id) as e";
            $sql .= " where 1";
            /**
             * Add search conditions
             */
            if (isset($params['search']) && trim($params['search']) != '') {
                //$sql.= " and (e.event_name  like '%" . $params['search'] . "%' or e.status like '%" . $params['search'] . "%')";
                $sql .= " and (e.event_name  like '%" . $params['search'] . "%' or e.status like '%" . $params['search'] . "%' or concat(campaigns_name,'-',aprimo_campaign_id) like '%" . $params['search'] . "%')";
            }
            if (!empty($params['start_date']) && !empty($params['end_date'])) {
                $sql .= " and date(e.start_date) <= '" . $params['end_date'] . "' and date(e.end_date) >= '" . $params['start_date'] . "' ";
            }
            if (isset($params['status_id']) && !empty($params['status_id'])) {
                $sql .= " and e.status_id in(" . implode(',', $params['status_id']) . ")";
            }
            if (isset($params['order']) && (isset($params['sort']) && !empty($params['sort']))) {
                $sql .= " order by " . $params['order'] . "  " . $params['sort'] . "";
            } else {
                $sql .= " order by e.last_modified desc ";
            }

            //echo $sql;exit;

            $dbResult = $objEvents->dbSelect($sql);
            return $dbResult;
        } catch (\Exception $ex) {
            throw new DataValidationException($ex->getMessage(), new MessageBag());
        }
    }

    /**
     * Get the list of Status
     * @return type array
     */
    function getEventStatusList() {

        $objEventStatus = new Status;
        $dbResult = $objEventStatus->get(['id', 'name'])->toArray();
        return $dbResult;
    }

    /**
     * Find the particular Events informations
     * 
     * @param int $command
     * @return type Events object
     */
    function getEventDetails($command) {
        
        $params = $command->dataToArray();
        $objEvents = new Events();
        $dbResult = $objEvents->dbTable('e')
                              ->leftJoin('campaigns as c', 'c.id', '=', 'e.campaigns_id')
                              ->leftJoin('campaigns_projects as cp', 'cp.id', '=', 'e.campaigns_projects_id')
                              ->leftJoin('statuses as s', 's.id', '=', 'e.statuses_id')
                              ->leftJoin('items as i', 'i.events_id', '=', 'e.id')
                              ->leftJoin('events_users as eu', 'eu.events_id', '=', 'e.id')
                              ->select('e.id', 'e.name as event_name', 's.id as status_id', 's.name as status', 'is_draft', 'e.start_date', 'e.end_date', 
                                       'c.name as campaigns_name', 'c.id as campaigns_id', 'c.aprimo_campaign_id', 
                                       'e.access_type', 'e.created_by', 'cp.id as campaigns_projects_id', 'cp.title as project_name', 'cp.aprimo_project_id')
                              ->selectRaw('count(i.events_id) as item_count')
                              ->selectRaw('group_concat( distinct eu.users_id) as events_users_id')
                              ->selectRaw('sum(if(i.publish_status = \'0\', 1, 0)) as unPublishedCount')
                              ->where('e.id', $params['id'])
                              ->groupBy('e.id')
                              ->get();
        
        return $dbResult;
    }

    /**
     * Get the Events count by status
     *
     * Find the Event count by type 0 -> All; 1 -> Global; 2 -> Draft;       
     * 
     * @param int $commad->last_modified_by 
     * @return Array of $arrResponse
     */
    function getEventsCountbyStatus($command) {

        $params = $command->dataToArray();
        $objEvents = new Events();
        $arrResponse = [];
        $dbResult = $objEvents->dbTable('e')
                        ->select('e.is_draft')
                        ->selectRaw('count(distinct e.id) as count')
                        ->whereRaw('e.is_draft = "0" OR (e.is_draft = "1" and e.created_by = ' . $params['last_modified_by'] . ')')
                        ->groupBy('e.statuses_id')->get()->toArray();

        $totalEvent = $globalEvent = 0;
        foreach ($dbResult as $row) {

            if ($row->is_draft == 0) {
                $globalEvent += $row->count;
                $arrResponse['global'] = $globalEvent;
            } else {
                $arrResponse['draft'] = $row->count;
            }
            $totalEvent += $row->count;
            $arrResponse['all'] = $totalEvent;
        }
        return $arrResponse;
    }

    /**
     * Get  the  dropdown values for Items filters
     * 
     * @return Array of $arrResponse
     */
    function getItemFilters($params) {
        $departments_id = 0;
        if (\Auth::check()) {
            $departments_id = \Auth::user()->departments_id;
        }
        $objItems = new Items();
        $objItemsDs = new \CodePi\Items\DataSource\ItemsDataSource();
        $permissions = $objItemsDs->getAccessPermissions($params['last_modified_by']);
        $permissions['departments_id'] = $departments_id;
        $filterCol = ['Supplier' => 'supplier', 'Dept Description' => 'dept_description',
            'Category Description' => 'category_description', 'Brand Name' => 'brand_name',
            'Ad block' => 'ad_block', 'Price ID' => 'price_id', 'Status' => 'status', 'Page' => 'page', 'Buyer User ID' => 'buyer_user_id'
        ];
        $arrResponse = [];

        foreach ($filterCol as $label => $column) {

            $arrResponse[$column] = ['label' => $label, 'key' => $column, 'val' => [], 'type' => 'multiselect'];

            if ($column != 'status') {

                $objResult = $objItems->dbTable('i')
                                ->join('items_editable as ie', 'ie.items_id', '=', 'i.id')
                                ->join('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                                ->leftJoin('users as u', 'u.id', '=', 'i.created_by')
                                ->selectRaw('TRIM(UPPER(' . $column . ')) as value')
                                ->where(function ($query) use ($params) {
                                    if (isset($params['events_id']) && !empty($params['events_id'])) {
                                        $query->where('i.events_id', '=', $params['events_id']);
                                    }
                                })->whereRaw($column . '!=\'\'')
                                ->where(function($query) use ($permissions, $params) {

                                    if (isset($permissions['items_access']) && ($permissions['items_access'] == '6' || $permissions['items_access'] == '4' || $permissions['items_access'] == '5')) {
                                        
                                    } else if (isset($permissions['items_access']) && ($permissions['items_access'] == '2' || $permissions['items_access'] == '3')) {
                                        $query->where('u.departments_id', $permissions['departments_id']);
                                    } else {
                                        $query->where('i.created_by', $params['last_modified_by']);
                                    }
                                })->groupBy('value')->orderBy('value', 'ASC')->get();
                foreach ($objResult as $objRow) {
                    $arrResponse[$column]['value'][] = $objRow->value;
                }
            }
        }

        $arrResponse['status']['value'] = array_values([true => 'PUBLISHED', false => 'PENDING']);
        return array_values($arrResponse);
    }

    /**
     * Get the list of Global data
     * @return Array $globalData
     */
    function getGlobalData($command) {
        //$params = $command->dataToArray();

        $globalData = [];
        $globalData['EventStatus'] = $this->getEventStatusList();
        //$globalData['ItemFilters'] = $this->getItemFilters($params);        
        //$globalData['EventsList'] = $this->getEventsDropdown($params); 
        $globalData['CellColorCodes'] = $this->getCellColourCodes('5');
        $globalData['EventAccess'] = $this->getEventsAccessType(); // $this->getCellColourCodes('6');
        return $globalData;
    }

    /**
     * Get Events list dropdown values
     * Only global events
     * @return array
     */
    function getEventsDropdown($params) {

        $objEvents = new Events();
        $totalCount = 0;
        $objEvents = $objEvents->where('is_draft', '0')
                               ->where('statuses_id', '!=', 5)
                               ->where(function ($query) use ($params) {
                                if (isset($params['events_id']) && !empty($params['events_id'])) {
                                    $query->where('id', '!=', $params['events_id']);
                                }
                               })->where(function($query)use($params) {
                                if (isset($params['search']) && trim($params['search']) != '') {
                                    $query->whereRaw("name like '%" . $params['search'] . "%' ");
                                }
                               })->orderBy('name', 'asc');
                                if (isset($params['page']) && !empty($params['page'])) {
                                    $objEvents = $objEvents->paginate($params['perPage']);
                                    $totalCount = $objEvents->total();
                                } else {
                                    $objEvents = $objEvents->get();
                                }
                            $objEvents->totalCount = $totalCount;

        return $objEvents;
    }

    /**
     *      
     * @return object
     */
    function getUnpublishedEventsByUsers() {
        $objEvents = new Events();
        $arrData = [];
        $sql = "select * from (select *, ifnull(cnt,0) as item_count, 
                if(is_draft = '0' , CASE 
                when (ifnull(cnt,0) >0 and ifnull(unpublish_cnt, 0) > 0) then 'ACTIVE' 
                when (ifnull(cnt,0)  >0 and ifnull(unpublish_cnt, 0) = 0) then 'PUBLISHED'
                when (ifnull(cnt,0)  = 0 and ifnull(unpublish_cnt, 0) = 0) then 'NEW'
                end, statusname
                ) as status,
                if(is_draft = '0' , case 
                when (ifnull(cnt,0) >0 and ifnull(unpublish_cnt, 0) > 0) then 2 
                when (ifnull(cnt,0)  >0 and ifnull(unpublish_cnt, 0) = 0) then 3
                when (ifnull(cnt,0)  = 0 and ifnull(unpublish_cnt, 0) = 0) then 1
                end, statusid
                ) AS status_id 
                from (
                select
                e.id,e.name as event_name, s.id as statusid, s.name as statusname, e.start_date, e.end_date, e.last_modified, e.is_draft
                from events as e
                left join statuses as s on s.id = e.statuses_id where e.is_draft = '0') as a
                left join (
                select count(i.events_id) as cnt, i.events_id, sum(if(i.publish_status = '0', 1, 0)) as unpublish_cnt, u.id as usersid, u.email, u.firstname
                from items as i
                left join users as u on u.id = i.created_by
                where i.items_type ='0' and u.`status` = '1' and u.is_register ='1' group by i.events_id, u.id) as b on a.id = b.events_id) as e 
                where is_draft = '0' and status_id = 2 and date(start_date) = date_add(current_date() , interval 7 day)
                order by e.last_modified desc ";
        $dbResult = $objEvents->dbSelect($sql);
        if (!empty($dbResult)) {
            foreach ($dbResult as $row) {
                $arrData[] = (array) $row;
            }
        }

        return $arrData;
    }

    /**
     * Assign HistoricalReferenceDate to each Items based on Events start & end date
     * @param int $eventsID
     * @return string
     */
    function getHistoricalReferenceDate($eventsID = 0) {

        $objEvents = new Events();
        $eventsInfo = $objEvents->where('id', $eventsID)->get(['start_date', 'end_date'])->toArray();
        $startDate = isset($eventsInfo[0]) && isset($eventsInfo[0]['start_date']) ? $eventsInfo[0]['start_date'] : "";
        $endDate = isset($eventsInfo[0]) && isset($eventsInfo[0]['end_date']) ? $eventsInfo[0]['end_date'] : "";

        if (!empty($startDate) && !empty($endDate)) {
            $historicalDates = date('m/d/y', strtotime($startDate)) . ' - ' . date('m/d/y', strtotime($endDate));
        } else {
            $historicalDates = "";
        }
        return $historicalDates;
    }

    /**
     * Update the HistoricalReferenceDate
     * @param obj $command
     */
//    function updateHistoricalReferenceDate($command) {
//        \DB::beginTransaction();
//        try {
//            $params = $command->dataToArray();
//            if (isset($params['id']) && !empty($params['id'])) {
//                $ids = [];
//                $isChanged = 0;
//                $dbResult = $this->getEventDetails($command);
//
//                foreach ($dbResult as $row) {
//                    $data = (array) $row;
//                }
//
//                $startDate = date('Y-m-d', strtotime($data['start_date']));
//                $endDate = date('Y-m-d', strtotime($data['end_date']));
//
//                if (trim($params['start_date']) != trim($startDate)) {
//                    $isChanged = 1;
//                }
//                if (trim($params['end_date']) != trim($endDate)) {
//                    $isChanged = 1;
//                }
//
//                if ($isChanged == 1) {
//                    $objItems = new Items();
//                    $primIds = $objItems->where('events_id', $params['id'])->where('is_no_record', '0')->get(['id'])->toArray();
//                    foreach ($primIds as $values) {
//                        $ids[] = $values['id'];
//                    }
//                    if (!empty($ids)) {
//                        $historicalDates = date('m/d/y', strtotime($params['start_date'])) . ' - ' . date('m/d/y', strtotime($params['end_date']));
//                        $objEdit = new ItemsEditable();
//                        $objEdit->whereIn('items_id', $ids)->update(['event_dates' => $historicalDates]);
//                    }
//                }
//            }
//            \DB::commit();
//        } catch (\Exception $ex) {
//            echo $ex->getMessage() . $ex->getFile() . $ex->getLine();
//            \DB::rollback();
//        }
//    }

    /**
     * 
     * @return array
     */
    function getCellColourCodes($module_id) {

        $objItemsDs = new ItemsDataSource();
//        $module_id = 5;
        $arrResult = $objItemsDs->getMasterDataOptions($module_id);
        $result = [];
        foreach ($arrResult as $key => $row) {
            $result[] = ['id' => $key, 'value' => $row];
        }
        return $result;
    }

    /**
     * Get list of events to be update into Archived status
     * If any event assigned with campaigns, interval of 1 months from campaigns end date
     * If no campaigns assigned with events, interval of 7 days from events end date
     * @return array
     */
    function getArchivedEventsList() {

        $objEvents = new Events();
        $sql = "SELECT 
                e.id AS eventId, c.id AS campaignId, DATE(e.end_date) AS eventEndData, DATE(c.end_date) AS campaignEndDate, DATE(c.out_of_market_date) AS outOfMarketDate
                FROM events AS e
                LEFT JOIN campaigns AS c ON e.campaigns_id = c.id
                WHERE e.is_draft = '0' AND e.statuses_id != 5";
        $result = $objEvents->dbSelect($sql);
        $intervalMonth = $objEvents->dbSelect('SELECT DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH) AS intervalMonth');
        $intervalDays = $objEvents->dbSelect('SELECT DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) AS intervalDay');

        $arrEvents = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                if (!empty($row->campaignId)) {
                    if ($intervalMonth[0]->intervalMonth == $row->outOfMarketDate) {
                        $arrEvents[] = $row->eventId;
                    }
                } else {
                    if ($intervalDays[0]->intervalDay == $row->eventEndData) {
                        $arrEvents[] = $row->eventId;
                    }
                }
            }
        }

        return $arrEvents;
    }

    /**
     * Update Archived status to events, through cron based on end date
     */
    function updateArchivedStatus() {
        DB::beginTransaction();
        $archivedStatus = 5;
        $count = 0;
        try {
            $objEvents = new Events();
            $arrEventId = $this->getArchivedEventsList();

            if (!empty($arrEventId)) {
                $objEvents->whereIn('id', $arrEventId)->update(['statuses_id' => $archivedStatus,
                    'last_modified' => PiLib::piDate(),
                    'gt_last_modified' => gmdate('Y-m-d H:i:s')
                ]);
                $count = count($arrEventId);
            }
            DB::commit();
        } catch (Exception $ex) {
            DB::rollback();
        }
        return $count;
    }

    /**
     * Prepare event informations to getItemsList service append the permissions based event informations
     * @param type $command
     * @return type
     */
    function getEventAdditionalInfoByPermissions($command) {
        $command->id = $command->event_id;
        $users_id = (isset($command->users_id) && $command->users_id != 0) ? $command->users_id : $command->last_modified_by;
        $result = $this->getEventDetails($command);
        $arrEvent = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $arrEvent['event_id'] = PiLib::piEncrypt($row->id);
                $arrEvent['status_id'] = $row->status_id;
                $arrEvent['event_status'] = $row->status;
            }
            /**
             * Get the status by users permissions
             */
            $objItemDs = new ItemsDataSource();
            $itemStatus = $objItemDs->getItemListStatusByUsers($command);
            $arrEvent['event_status'] = (isset($arrEvent['status_id']) && $arrEvent['status_id'] != 4 && $arrEvent['status_id'] != 5) ? $itemStatus : $arrEvent['event_status'];
            unset($arrEvent['status_id']);
            /**
             * Get Event Items Count (Linked Items & Result Items) by Permissions
             */
            $itemsListUserId = isset($command->itemsListUserId) ? $command->itemsListUserId : 0;
            $departmentId = isset($command->department_id) ? $command->department_id : 0;
            $itemCount = $objItemDs->getItemsCount($command->event_id, $users_id, $itemsListUserId, $departmentId);
            $arrEvent['itemCount'] = $itemCount;
        }
        return $arrEvent;
    }


    /**
     * Get users list dropdown values
     * Only active users
     * @return array
     */
    function getUsersDropdown($command) {

        $loggedUser = (Auth::check()) ? Auth::user()->id : 0;
        $params = $command->dataToArray();
        $objUsers = new Users();
        $objUsers = $objUsers->select('id', 'firstname', 'lastname')
                             //->where('id', '!=', $loggedUser)
                             ->where('status', '1')
                             //->where('is_register', '1')
                             ->where(function($query)use($params) {
                               if (isset($params['search']) && trim($params['search']) != '') {
                                   $query->where('firstname', 'like', '%' . $params['search'] . '%')
                                         ->orWhere('lastname', 'like', '%' . $params['search'] . '%');
                                }
                             })->orderBy('firstname', 'asc')->get();
                             
//        if (isset($params['page']) && !empty($params['page'])) {
//            $objUsers = $objUsers->paginate($params['perPage']);
//        } else {
//            $objUsers = $objUsers->get();
//        }
        return $objUsers;
    }

    /**
     * Add and Update the Event users access informations
     * 
     * @params array $command->users_id 
     * @params integer $id

     * @return Event users Objects
     */

    function userAccessEvents($command, $id, $createdBy) {
        $params = $command->dataToArray();
        unset($params['id']);
        $result = [];
        $objEventsUsers = new EventsUsers();
        $objEventsUsers->dbTransaction();
        try {
            $objEventsUsers->where('events_id', $id)->delete();
            if (isset($params['access_type']) && $params['access_type'] == '2') {
                $params['users_id'] = array_merge($params['users_id'], [$createdBy]);
                foreach ($params['users_id'] as $user) {
                    unset($params['users_id']);
                    $params['users_id'] = $user;
                    $params['events_id'] = $id;
                    $result[] = $objEventsUsers->saveRecord($params);
                }
            }
            $objEventsUsers->dbCommit();
        } catch (\Exception $ex) {
            $objEventsUsers->dbRollback();
        }
        return $result;
    }

    /**
     * Check if logged in user has event access
     * 
     * @return array
     */
//    function checkUserCanAccessEvents($id) {
//        $isUserAccess = false;
//        $userId = Auth::user()->id;
//        $objEventsUsers = new EventsUsers();
//        $result = $objEventsUsers->where('users_id', $userId)->where('events_id', $id)->get();        
//        if (count($result) > 0) {
//            $isUserAccess = true;
//        }
//        return $isUserAccess;
//    }

    /**
     * 
     * @param type $id
     * @return type
     */
//    function getEventUsers($id) {
//        $arrUsers = [];
//        $loggedUser = (Auth::check()) ? Auth::user()->id : 0;
//        $objEventsUsers = new EventsUsers();
//        $result = $objEventsUsers->select('users_id')->where('events_id', $id)
//                                //->where('users_id', '!=', $loggedUser)
//                                ->get()->toArray();
//        if (!empty($result)) {
//            foreach ($result as $row) {
//                $arrUsers[] = $row['users_id'];
//            }
//        }
//        return $arrUsers;
//    }

    /**
     * Assign Events Access Type Array
     * @return type
     */
    function getEventsAccessType(){
        $accessType[] = array(0 => array('id' => 1, 'value' => 'All Users'), 
                              1 => array('id' => 2, 'value' => 'Specific Users')
                             );       
        return $accessType;

    }
    
    /**
     * Check Event is Archive or not
     * @param type $intEventId
     * @return boolean
     */
    function isArchivedEvents($intEventId) {
        $isArchived = false;
        $objEvents = new Events();
        $status = $objEvents->where('id', $intEventId)->first();
        
        if ($status->statuses_id == 5) {            
            $isArchived = true;
        }
        return $isArchived;
    }
    
    /**
     * Update Event Date -> HistoricalReffernceDate 
     * Update Aprimo Details in items, if any value changed while updating events
     * Update Activity logs details
     * @param type $dbData
     * @param type $params
     */
    function updateEventDependencyData($dbData, $params) {
        DB::beginTransaction();
        try {
            $isChangedCamp = 0;
            $isChangedDate = 0;
            $arrItemId = [];
            $startDate = date('Y-m-d', strtotime($dbData->start_date));
            $endDate = date('Y-m-d', strtotime($dbData->end_date));
            if ($dbData->campaigns_id != $params['campaigns_id'] || $dbData->campaigns_projects_id != $params['campaigns_projects_id']) {
                $isChangedCamp = 1;
            } else if (trim($params['start_date']) != trim($startDate) || trim($params['end_date']) != trim($endDate)) {
                $isChangedDate = 1;
            }

            if (!empty($isChangedCamp) || !empty($isChangedDate)) {
                $objItems = new Items();
                $primIds = $objItems->where('events_id', $params['id'])->where('is_no_record', '0')->get(['id'])->toArray();
                foreach ($primIds as $values) {
                    $arrItemId[] = $values['id'];
                }
                if (!empty($arrItemId)) {
                    $aprimoData = !empty($isChangedCamp) ? $this->getAprimoDetails($params['id']) : [];
                    $historicalDates = !empty($isChangedDate) ? array('event_dates' => PiLib::piDate($params['start_date'], 'm/d/y') . ' - ' . PiLib::piDate($params['end_date'], 'm/d/y')) : [];
                    $updateValue = array_merge($historicalDates, $aprimoData);
                    if (!empty($updateValue)) {
                        $updateValue['tracking_id'] = $this->unique_id . '-0';
                        $objItems->whereIn('id', $arrItemId)->where('items_type', '0')->update(['tracking_id' => $this->unique_id . '-0', 'last_modified_by' => $params['last_modified_by'], 'last_modified' => $params['last_modified']]);
                        $objEdit = new ItemsEditable();
                        $objEdit->whereIn('items_id', $arrItemId)->update($updateValue);
                        $objLogs = new ActivityLog();
                        $logData = $objLogs->setActivityLog(array('events_id' => $params['id'], 'actions' => 'update', 'users_id' => $params['last_modified_by'], 'count' => count($arrItemId), 'type' => '0', 'tracking_id' => $this->unique_id));
                        $objLogs->updateActivityLog();
                        unset($updateValue, $logData);
                    }
                }
                unset($arrItemId);
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
    }

    /**
     * Get Aprimo campaigns details
     * @param Integer $intEventsId
     * @return array
     */
//    function getAprimoDetails($intEventsId = 0) {
//        $itemAprimo = [];
//        $objEvents = new Events();
//        $objEvents = $objEvents->dbTable('e')
//                               ->join('campaigns as c', 'c.id', '=', 'e.campaigns_id')
//                               ->leftJoin('campaigns_projects as cp', function($join) use ($intEventsId) {
//                                    $join->on('cp.campaigns_id', '=', 'c.id')
//                                         ->where('cp.id', '=', 'e.campaigns_projects_id');
//                               })
//                               ->where('e.id', $intEventsId)
//                               ->select('c.aprimo_campaign_id', 'c.name as aprimo_campaign_name', 'cp.aprimo_project_id', 'cp.title as aprimo_project_name')
//                               ->limit(1)
//                               ->get();
//        if (!empty($objEvents)) {
//            foreach ($objEvents as $row) {
//                $itemAprimo[] = (array) $row;
//            }
//        }
//        return array_shift($itemAprimo);
//    }
    
    function getAprimoDetails($intEventsId = 0) {
        $itemAprimo = [];
        $objEvents = new Events();
        $dbResult = $objEvents->dbTable('e')
                              ->join('campaigns as c', 'c.id', '=', 'e.campaigns_id')
                              ->leftJoin('campaigns_projects as cp', 'cp.id', '=', 'e.campaigns_projects_id')                               
                              ->where('e.id', $intEventsId)
                              ->select('c.aprimo_campaign_id', 'c.name as aprimo_campaign_name', 'cp.aprimo_project_id', 'cp.title as aprimo_project_name')
                              ->limit(1)
                              ->get();
        if (!empty($dbResult)) {
            foreach ($dbResult as $row) {
                $itemAprimo[] = (array) $row;
            }
        }
        
        return array_shift($itemAprimo);
    }

}
