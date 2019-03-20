<?php

namespace CodePi\Events\DataTransformers;

use CodePi\Base\Eloquent\Events;
use CodePi\Base\DataTransformers\PiTransformer;
use CodePi\Base\Libraries\PiLib;
use CodePi\Events\DataSource\EventsDataSource;
use Auth;
use CodePi\Items\Utils\ItemsUtils;

class EventsDataTransformers extends PiTransformer {

    public $defaultColumns = ['id', 'event_name', 'start_date', 'end_date', 'status_id', 'status', 'item_count', 'campaigns_id', 'campaigns_name', 'aprimo_campaign_id', 'created_by', 'campaigns_projects_id', 'project_name', 'aprimo_project_id'];
    public $encryptColoumns = [];

    /**
     * @param object $objEvents
     * @return array It will loop all records of events table
     */
    function transform($objEvents) {

        $arrResult = $this->mapColumns($objEvents);
        $arrResult['id'] = PiLib::piEncrypt($objEvents->id);
        $arrResult['loggedInUserAccess'] = ($objEvents->access_type == '1') ? true : $this->eventsAccessPermission($objEvents->events_users_id);
        $arrResult['users_id'] = ($objEvents->access_type == '2') && !empty($objEvents->events_users_id) ? array_map('intval', explode(',', $objEvents->events_users_id)) : [];        
        $arrResult['event_name'] = PiLib::filterStringDecode($objEvents->event_name);
        $arrResult['start_date'] = PiLib::piDate($objEvents->start_date, 'M d, Y');
        $arrResult['end_date'] = PiLib::piDate($objEvents->end_date, 'M d, Y');
        $arrResult['status_id'] = $objEvents->status_id;
        $arrResult['status'] = ItemsUtils::setEventStatus($objEvents->status_id, $objEvents->item_count, $objEvents->unPublishedCount, $objEvents->status);
        $arrResult['item_count'] = $objEvents->item_count;
        $arrResult['access_type'] = (int) $objEvents->access_type;
        $arrResult['created_by'] = $objEvents->created_by;
        $arrResult['campaigns_id'] = isset($objEvents->campaigns_id) ? $objEvents->campaigns_id : "";
        $arrResult['campaigns_name'] = isset($objEvents->campaigns_name) ? PiLib::filterStringDecode($objEvents->campaigns_name) : "";
        $arrResult['aprimo_campaign_id'] = isset($objEvents->aprimo_campaign_id) ? $objEvents->aprimo_campaign_id : "";
        $arrResult['campaigns_projects_id'] = isset($objEvents->campaigns_projects_id) ? $objEvents->campaigns_projects_id : "";
        $arrResult['project_name'] = isset($objEvents->project_name) ? PiLib::filterStringDecode($objEvents->project_name) : "";
        $arrResult['aprimo_project_id'] = isset($objEvents->aprimo_project_id) ? $objEvents->aprimo_project_id : "";
        return $this->filterData($arrResult);
    }

    /**
     * Check logged user have event access perm 
     * @param type $events_users_id
     * @return boolean
     */
    function eventsAccessPermission($events_users_id) {
        $accessPerm = false;
        $loggedUser = (Auth::check()) ? Auth::user()->id : 0;
        if (!empty($events_users_id)) {
            $arrUserId = explode(',', $events_users_id);
            if (in_array($loggedUser, $arrUserId)) {
                $accessPerm = true;
            }
        }
        return $accessPerm;
    }

}
