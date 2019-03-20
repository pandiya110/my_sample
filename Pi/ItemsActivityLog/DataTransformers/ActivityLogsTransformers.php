<?php

namespace CodePi\ItemsActivityLog\DataTransformers;

use CodePi\Base\DataTransformers\PiTransformer;
use URL,
    DB;
use CodePi\Base\Libraries\PiLib;
use Auth;
class ActivityLogsTransformers extends PiTransformer {

    /**
     * @param object $objHeaders
     * @return array It will loop all records of Users table
     */
    function transform($row) {
        $arrResult = $this->mapColumns($row);
        $date = $this->logDate($row->last_modified);
        $loggedId = (Auth::check()) ? Auth::user()->id:0;
        $user_time = PiLib::UserTimezone($row->last_modified);
        $timeFormat = PiLib::piDate($user_time, 'h:i A');
        $name = ($loggedId == $row->users_id) ? '<b>Me</b>' : '<b>' . $row->firstname . ' ' . $row->lastname . '</b>';
        $message = $row->descriptions . ' by ' . $name;
        $arrResult[$date]['date'] = $date;
        $arrResult[$date]['list']['message'] = $message;
        $arrResult[$date]['list']['date_format'] = $date;
        $arrResult[$date]['list']['time'] = $timeFormat;
        
        return $this->filterData($arrResult);
    }
    
    function logDate($date) {
        $date = PiLib::piDate($date, 'Y-m-d');
        $dateFormat = '';
        if ($date == date('Y-m-d')) {
            $dateFormat = 'Today';
        } else if ($date == date('Y-m-d', strtotime("-1 days"))) {
            $dateFormat = 'Yesterday';
        } else {
            $dateFormat = PiLib::piDate($date, 'M j, Y');
        }
        return $dateFormat;
    }

}
