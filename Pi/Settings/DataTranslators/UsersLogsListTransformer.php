<?php

namespace CodePi\Settings\DataTranslators;

use League\Fractal\TransformerAbstract;
#use League\Fractal\Manager;
#use League\Fractal\Resource\Collection;
#use League\Fractal\Resource\Item; 
use CodePi\Login\Eloquant\UsersLog;
use CodePi\Base\Libraries\PiLib;
class UsersLogsListTransformer extends TransformerAbstract {

    function transform(UsersLog $usersLog) {
         $objPiLib=new PiLib;
        return [
//    	    'login_time' =>($usersLog->gt_date_added ===NULL) ? "" :$objPiLib->getTimezoneDate($usersLog->gt_date_added,session('timezone')),
//    	    'logout_time' =>($usersLog->gt_last_modified ===NULL) ? "" : $objPiLib->getTimezoneDate($usersLog->gt_last_modified,session('timezone')),
    	    'login_time' =>($usersLog->gt_date_added ===NULL) ? "" : $objPiLib->getUserTimezoneDate($usersLog->gt_date_added),
    	    'logout_time' =>($usersLog->gt_last_modified ===NULL) ? "" : $objPiLib->getUserTimezoneDate($usersLog->gt_last_modified),
    	    'browser' => $usersLog->browser,    
    	    'browser_version' => $usersLog->browser_version, 
    	    'user_agent' => $usersLog->user_agent, 
    	    'os' => $usersLog->os,   
    	    'device_type' => $usersLog->device_type,
    	    'users_id' => $usersLog->users_id,
    	    'created_by' => $usersLog->created_by,
    	    'date_added' => $usersLog->date_added,
    	    'last_modified' => $usersLog->last_modified,
    	    'last_modified_by' => $usersLog->last_modified_by,
    	    'gt_date_added' => $usersLog->gt_date_added,
    	    'gt_last_modified' => $usersLog->gt_last_modified,
    	    'ip_address' => $usersLog->ip_address,
            'fullname' => $usersLog->fullname
        ];
    }

}
