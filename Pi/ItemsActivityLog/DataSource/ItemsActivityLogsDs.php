<?php

namespace CodePi\ItemsActivityLog\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\History;
use CodePi\Base\Eloquent\Events;
use CodePi\Base\Eloquent\Users;
use GuzzleHttp;
use Auth,
    Session;
use URL,
    DB;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Base\Eloquent\ActivityLogs;
use CodePi\Events\DataSource\EventsDataSource;
use CodePi\Base\Eloquent\ItemsHeaders;
use CodePi\RestApiSync\DataSource\ItemsDataSource as SyncItemDs;


class ItemsActivityLogsDs {

    /**
     * Get the items activity logs by users
     * @param object $command
     * @return array
     */
    function getActivityLogs($command) {

        $params = $command->dataToArray();
        $objActLogs = new ActivityLogs();
        $permissions = $this->applyPersmissions($params['last_modified_by']);
        $dbResult = $objActLogs->dbTable('al')
                               ->join('users as u', 'u.id', '=', 'al.users_id')
                               ->select('u.firstname', 'u.lastname', 'al.users_id', 'profile_image_url', 'al.events_id', 'al.descriptions', 'al.tracking_id', 'al.actions', 'al.last_modified', 'al.tracking_id', 'al.type')
                               ->where(function ($query) use ($params) {
                                    if (isset($params['events_id']) && !empty($params['events_id'])) {
                                        $query->where('al.events_id', $params['events_id']);
                                    }
                               })->where(function($query) use ($permissions, $params) {

                               if (isset($permissions['items_access']) && ($permissions['items_access'] == '6' || $permissions['items_access'] == '4' || $permissions['items_access'] == '5')) {

                               } else if (isset($permissions['items_access']) && ($permissions['items_access'] == '2' || $permissions['items_access'] == '3')) {
                                    $query->where('u.departments_id', $permissions['departments_id']);
                               } else {
                                    $query->where('u.id', $params['last_modified_by']);
                               }
                        })->orderBy('al.last_modified', 'desc');
                        if (isset($params['page']) && !empty($params['page'])) {
                            $dbResult = $dbResult->paginate($params['perPage']);
                        } else {
                            $dbResult = $dbResult->get();
                        }

            return $dbResult;
    }

    /**
     * Get permissions by users
     * @param type $users_id
     * @return array
     */
    function applyPersmissions($users_id) {
        $departments_id = 0;
        if (\Auth::check()) {
            $departments_id = \Auth::user()->departments_id;
        }
        $objItemsDs = new ItemsDataSource();
        $permissions = $objItemsDs->getAccessPermissions($users_id);
        $permissions['departments_id'] = $departments_id;
        return $permissions;
    }

    /**
     * Save Activity logs
     * @param array $data
     * @return object
     */
    function saveActivityLogs($data) {
        $objLogs = new ActivityLogs();
        $saveDetails = [];
        $objLogs->dbTransaction();
        try {
            $saveDetails = $objLogs->saveRecord($data);
            $objLogs->dbCommit();
        } catch (\Exception $ex) {
            $objLogs->dbRollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }
        return $saveDetails;
    }


    function getActivityLogsDetails($command) {
        $params = $command->dataToArray();
        $objLogs = new History();
        $actionArray = ['insert', 'delete', 'copy', 'moved'];

        $dbResult = $objLogs->dbTable('h')
                            ->leftJoin('activity_logs as al', function($join) use($params) {
                                $join->on('al.tracking_id', '=', 'h.tracking_id');
                            })->selectRaw('*, h.id as prim_id')
                            ->where('h.tracking_id', $params['tracking_id'])
                            ->where('al.type', $params['type'])
                            ->get();


        return $dbResult;
    }

    /**
     * Assign values for actions column values
     * @param string $column
     * @param string $value
     * @return string
     */
    function actionsColArray($column, $value) {

        if ($column == 'is_excluded') {
            $value = ($value == '0') ? 'Activated' : 'Excluded';
        } else if ($column == 'publish_status') {
            $value = ($value == '1') ? 'Published' : 'Unpublished';
        } else if ($column == 'item_sync_status') {
            $value = ($value == '1') ? 'Re-Sync' : 'Sync';
        } else if ($column == 'attributes') {
            $objItemsDs = new ItemsDataSource();
            $result = $objItemsDs->getAttributesSelectedValues($value);
            $value = $result;
        } else if ($column == 'local_sources') {
            $objSyncItemDs = new SyncItemDs();
            $value = $objSyncItemDs->getVendorSupplyValue($value);
        } else {

            $value = $value;
        }

        return $value;
    }

    /**
     * Get Column Lable by column key
     * @param string $key
     * @return string
     */
    function getColumnLabelByColumnKey($key) {
        $nonHeaders = ['Excluded Status' => 'is_excluded', 'Published Status' => 'publish_status', 'Sync Status' => 'item_sync_status', 'Last Modified' => 'last_modified'];
        if (!in_array($key, $nonHeaders)) {
            $obj = new ItemsHeaders();
            $result = $obj->where('column_name', $key)->first();
            return $result->column_label;
        } else {
            $label = array_flip($nonHeaders);

            return isset($label[$key]) ? $label[$key] : $key;
        }
    }

    /**
     * Get item number from history values
     * @param int $history_id
     * @param int $items_id
     * @return array
     */
    function getItemNbrFromHistory($history_id, $items_id) {

        $data = [];
        $obj = new History();
        $result = $obj->where('id', $history_id)->where('items_id', $items_id)->where('table_name', 'items')->get(['total_history'])->toArray();

        foreach ($result as $row) {
            $data[$items_id] = \GuzzleHttp\json_decode($row['total_history']);
        }
        $itemNbr = [];
        foreach ($data as $values) {
            $itemNbr[$items_id] = $values->searched_item_nbr[0];
        }
        return $itemNbr;
    }
    /**
     * Get the Items type Result items or Linked items
     * @param int $history_id
     * @param int $items_id
     * @return array
     */
    function getItemsTypeByHistoryId($history_id, $items_id) {
        $obj = new History();
        $result = $obj->where('id', $history_id)->where('items_id', $items_id)->where('table_name', 'items')->get(['total_history'])->toArray();
        $item_type = [];
        foreach ($result as $row) {
            $data = \GuzzleHttp\json_decode($row['total_history']);
            foreach ($data as $key => $val) {
                if ($key == 'items_type') {
                    $item_type[$items_id] = isset($val[0]) ? $val[0] : '0';
                }
            }
        }
        
        return $item_type;
    }

}
