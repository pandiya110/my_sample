<?php

namespace CodePi\SyncItems\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Redirector;
use Response;
use Session;
use CodePi\Base\Commands\CommandFactory;
use CodePi\SyncItems\Commands\ResyncItems;
use CodePi\SyncItems\Commands\SaveSyncItems;
use CodePi\SyncItems\Commands\CheckAvailability;
use CodePi\SyncItems\Commands\SaveApiAvailability;

class SyncController extends PiController {

    public function saveSyncItems(Request $request) {
        $data = $request->all();
        $command = new SaveSyncItems($data);
        return $this->run($command, trans('SyncItems::messages.S_SyncItem'), trans('SyncItems::messages.E_SyncItem'));
    }

    /**
     * Re-sync the items from masters
     * @param Request $request
     * @return Response
     */
    public function reSyncItems(Request $request) {
        $data = $request->all();
        $command = new ResyncItems($data);
        return $this->run($command, trans('SyncItems::messages.S_SyncItem'), trans('SyncItems::messages.E_SyncItem'));
    }

    public function checkAvailability(Request $request) {
        $data = $request->all();
        $command = new CheckAvailability($data);
        return $this->run($command, trans('SyncItems::messages.S_SyncItem'), trans('SyncItems::messages.E_SyncItem'));
    }

    public function saveApiAvailability(Request $request) {
        $data = $request->all();
        $command = new SaveApiAvailability($data);
        return $this->run($command, trans('SyncItems::messages.S_SaveApiAvailability'), trans('SyncItems::messages.E_SaveApiAvailability'));
    }

}
