<?php

namespace CodePi\ItemsActivityLog\Logs;

use App\Events\ItemsActivityLogs;
use App\Events\UpdateLogDeleteItems;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ActivityLog
 *
 * @author enterpi
 */
class ActivityLog {
    /**
     * Define array for type of actions
     * @var Array
     */
    public $actions = array('insert' => 'Inserted',
                            'delete' => 'Deleted',
                            'update' => 'Updated',
                            'copy' => 'Copied',
                            'move' => 'Moveded',
                            'excluded' => 'Excluded',
                            'activated' => 'Activated',
                            'duplicate' => 'Duplicated',
                            'sync' => 'ReSync',
                            'addrow' => 'New Row Added',
                            'export' => 'Event Exported',
                            'publish' => 'Publihsed',
                            'unpublish' => 'UnPublihsed',
    );
    /**
     *
     * @var Array
     */
    public $type = array('0' => 'Items', '1' => 'Linked Items');
    /**
     *
     * @var Array
     */
    var $logData;
    

    function __construct() {
        
    }
    /**
     * Prepare Logs Descriptions
     * @param type $logData
     */
    function setActivityLog($logData) {

        $actionType = $this->actions[$logData['actions']];
        $count = isset($logData['count']) ? $logData['count'] : '';
        $type = isset($logData['type']) ? $this->type[$logData['type']] : '';
        $descriptions = isset($logData['descriptions']) ? $logData['descriptions'] : $count . ' ' . $type . ' ' . $actionType;
        $trackingId = $logData['tracking_id'] . '-' . $logData['type'];
        $this->logData[] = array_merge($logData, array('descriptions' => $descriptions, 'tracking_id' => $trackingId));
    }
    /**
     * Get the Activity logs data
     * @return Array
     */
    function getActivityLog() {

        return $this->logData;
    }
    /**
     * Add Activity logs
     * @return boolean
     */
    function updateActivityLog() {
        try {
            $logData = $this->getActivityLog();
            
            if (is_array($logData) && !empty($logData)) {

                foreach ($logData as $type => $log) {
                    if (isset($log['count']) && $log['count'] > 0 || $log['actions'] == 'addrow' || $log['actions'] == 'export') {
                        $log['date_added'] = isset($log['date_added']) ? $log['date_added'] : PiLib::piDate();
                        $log['last_modified'] = isset($log['last_modified']) ? $log['last_modified'] : PiLib::piDate();
                        /**
                         * Event to create the activity logs
                         */
                        event(new ItemsActivityLogs($log));
                        if ($log['actions'] == 'delete') {
                            $itemsId = isset($log['items_id']) && !empty($log['items_id']) ? $log['items_id'] : [];
                            /**
                             * Event to update the tracking id to delete items number in history
                             */
                            event(new UpdateLogDeleteItems($itemsId, $log['tracking_id']));
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
             $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
             CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
        }

        return true;
    }

}
