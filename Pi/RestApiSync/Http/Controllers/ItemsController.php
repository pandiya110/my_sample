<?php

namespace CodePi\RestApiSync\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use CodePi\RestApiSync\Commands\GetItems;
use CodePi\RestApiSync\Commands\SyncItems;
use CodePi\RestApiSync\Commands\SyncItemsChannels;
use PHPUnit\Util\Json;

class ItemsController extends PiController {

    public function getItems(Request $request) {
        $data = $request->all();
        $command = new GetItems($data);
        return $this->run($command, trans('Events::contacts.S_GetContacts'), trans('Events::contacts.E_GetContacts'));
    }

    public function syncItems(Request $request) {

        $data = $request->all();
        $command = new SyncItems($data);
        return $this->run($command, trans('Events::contacts.S_GetContacts'), trans('Events::contacts.E_GetContacts'));
    }

    public function syncItemsChannels(Request $request) {

        $data = $request->all();
        $command = new SyncItemsChannels($data);
        return $this->run($command, trans('Events::contacts.S_GetContacts'), trans('Events::contacts.E_GetContacts'));
    }

    function docs(Request $request) {
        return view('docs.api.index');
    }

}
