<?php

namespace CodePi\Aprimo\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Redirector;
use Response;
use Session;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\DataTransformers\DataSourceResponse;
use CodePi\Base\Exceptions\DataValidationException;
use CodePi\Aprimo\Commands\UpdateAprimoAuthDetails;
use CodePi\Aprimo\Commands\GetAprimoActivities;
use CodePi\Aprimo\Commands\GetAprimoProjects;

class AprimoController extends PiController {

    public function updateAprimoAuthDetails(Request $request) {
        $data = $request->all();
        $command = new UpdateAprimoAuthDetails($data);
        return $this->run($command, trans('Campaigns::messages.S_Campaigns'), trans('Campaigns::messages.E_Campaigns'));
    }

    public function getAprimoActivities(Request $request) {
        $data = $request->all();
        $command = new GetAprimoActivities($data);
        return $this->run($command, trans('Campaigns::messages.S_Campaigns'), trans('Campaigns::messages.E_Campaigns'));
    }
    
    public function getAprimoProjects(Request $request) {
        $data = $request->all();
        $command = new GetAprimoProjects($data);
        return $this->run($command, trans('Campaigns::messages.S_Projects'), trans('Campaigns::messages.E_Projects'));
    }

}
