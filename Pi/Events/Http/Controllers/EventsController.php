<?php

namespace CodePi\Events\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use Response;
use CodePi\Base\DataTransformers\DataSourceResponse;
use CodePi\Base\Exceptions\DataValidationException;
use CodePi\Events\Commands\AddEvents;
use CodePi\Events\Commands\GetEvents;
use CodePi\Events\Commands\GetGlobal;
use CodePi\Events\Commands\GetEventDetails;
use CodePi\Events\Commands\GetEventsDropdown;
use CodePi\Events\Commands\GetUsersDropdown;


class EventsController extends PiController {

    /**
     * Add & Update the Events
     * 
     * @param Request $request
     * @return Response
     */
    public function addEvents(Request $request) {

        $data = $request->all();
        $command = new AddEvents($data);
        return $this->run($command, trans('Events::messages.S_AddEvents'), trans('Events::messages.E_AddEvents'));
    }

    /**
     * Get the list of events
     * 
     * @param Request $request
     * @return Response
     */
    public function getEventsList(Request $request) {

        $data = $request->all();
        $data['is_draft'] = '';
        if ($data['type'] == 1) {
            $data['is_draft'] = '0';
        } else if ($data['type'] == 2) {
            $data['is_draft'] = '1';
        }
        /* filters for status */
        if (isset($data['status_id']) && !empty($data['status_id'])) {
            $arrayStatus = [1, 2, 3, 4, 5];

            switch ($data['is_draft']) {
                /*
                 * display only global events, if the selected status id is draft, ignored draft events in Global Tab
                 */
                case '0' :
                    $input = array_search(4, $arrayStatus);
                    unset($arrayStatus[$input]);
                    $final = array_intersect($data['status_id'], $arrayStatus);
                    if (!empty($final)) {
                        $data['status_id'] = $final;
                    } else {
                        $data['status_id'] = $arrayStatus;
                    }
                    $status_id = $data['status_id'];
                    break;
                /*
                 * display only draft events, if the selected status id is global, ignored gloabl events in Draft Tab
                 */
                case '1' :
                    $data['status_id'] = [4];
                    $status_id = $data['status_id'];
                    break;
                default :
                    $status_id = $data['status_id'];
                    break;
            }
        }
        unset($data['type']);

        $command = new GetEvents($data);
        return $this->run($command, trans('Events::messages.S_GetEvents'), trans('Events::messages.E_GetEvents'));
    }

    /**
     * Get the Dropdown values to load the Filters in Grid list (Events & Items Grid)
     *  
     * @param Request $request
     * @return Response
     */
    public function getGlobal(Request $request) {
        $data = $request->all();
        $command = new GetGlobal($data);
        return $this->run($command, trans('Events::messages.G_Global'), trans('Events::messages.E_Global'));
    }

    /**
     * Show the details of Event
     * 
     * @param Request $request     
     * @return type
     */
    public function getEventDetails(Request $request) {
        $data = $request->all();
        $command = new GetEventDetails($data);
        return $this->run($command, trans('Events::messages.S_EventInfo'), trans('Events::messages.E_EventInfo'));
    }

    /**
     * Get Events list in dropdown
     * @param Request $request
     * @return Response
     */
    public function getEventsDropDown(Request $request) {
        $data = $request->all();
        $command = new GetEventsDropdown($data);
        return $this->run($command, trans('Events::messages.S_EventsDropDown'), trans('Events::messages.E_EventsDropDown'));
    }

    /**
     * Get Users list in dropdown
     * @param Request $request
     * @return Response
     */
    public function getUsersDropDown(Request $request) {
        $data = $request->all();
        $command = new GetUsersDropdown($data);
        return $this->run($command, trans('Events::messages.S_UserDropDown'), trans('Events::messages.E_UserDropDown'));
    }

}
