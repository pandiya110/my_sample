<?php

namespace CodePi\ImportExportLog\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use CodePi\ImportExportLog\Commands\ListLogs;
use CodePi\ImportExportLog\Commands\GetSystemLog;

/**
 * @access public
 * @ignore It will handle master module operations
 */
class ImportExportLogController extends PiController {

    /**
     * List of campaigns
     * @param object $request
     * @return campaign viwe file
     */

//    public function index(Request $request) {
//        return view('importStores');
//    }
//      
    public function listLogs(Request $objRequest) {        
        $data = $objRequest->all();
        $command = new ListLogs($data);
        return $this->run($command,trans('Master::messages.S_MasterItem'),trans('Master::messages.E_MasterItem')); 
    }
    
    public function systemLogs(Request $objRequest) {        
        $data = $objRequest->all();
        $command = new GetSystemLog($data);
        return $this->run($command,trans('Master::messages.S_MasterItem'),trans('Master::messages.E_MasterItem')); 
    }
          
}


