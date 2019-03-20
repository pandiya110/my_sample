<?php

namespace CodePi\ItemsActivityLog\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Response;
use CodePi\ItemsActivityLog\Commands\GetActivityLogs;
use CodePi\ItemsActivityLog\Commands\GetActivityLogsDetails;

class ActivityLogController extends PiController {

    public function getActivityLogs(Request $request) {
        $data = $request->all();
        $command = new GetActivityLogs($data);
        return $this->run($command, trans('ItemsActivityLog::messages.S_ActivityLogs'), trans('ItemsActivityLog::messages.E_ActivityLogs'));
    }
    
    public function getActivityLogsDetails(Request $request) {
        $data = $request->all();
        $command = new GetActivityLogsDetails($data);
        return $this->run($command, trans('ItemsActivityLog::messages.S_ActivityLogs'), trans('ItemsActivityLog::messages.E_ActivityLogs'));
    }

}
