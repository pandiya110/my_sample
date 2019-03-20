<?php

namespace CodePi\RestApiSync\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use CodePi\RestApiSync\Commands\GetEvents;
use CodePi\RestApiSync\Commands\SyncEvents;
use CodePi\Base\DataTransformers\DataResponse;
use PHPUnit\Util\Json;
use Response;
use CodePi\Base\DataSource\Elastic;

class EventsController extends PiController {

    public function getEvents(Request $request) {

        $data = $request->all();
        $command = new GetEvents($data);
        return $this->run($command, trans('RestApiSync::message.S_EventsList'), trans('RestApiSync::message.E_EventsList'));
    }

    public function syncEvents($id = 0) {
        $data = ['id' => $id];
        $command = new SyncEvents($data);
        return $this->run($command, trans('Events::contacts.S_GetContacts'), trans('Events::contacts.E_GetContacts'));
    }

}
