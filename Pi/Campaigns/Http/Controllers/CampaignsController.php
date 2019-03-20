<?php

namespace CodePi\Campaigns\Http\Controllers;

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
use CodePi\Campaigns\Commands\GetCampaignsList;
use CodePi\Campaigns\Commands\SaveCampaigns;
use CodePi\Campaigns\Commands\GetCampaignsDropdown;
use CodePi\Campaigns\Commands\GetProjectsByCampaign;

class CampaignsController extends PiController {

    /**
     * Get  the campaigns list
     * @param Request $request
     * @return Response
     */
    public function getCampaignsList(Request $request) {
        $data = $request->all();
        $command = new GetCampaignsList($data);
        return $this->run($command, trans('Campaigns::messages.S_Campaigns'), trans('Campaigns::messages.E_Campaigns'));
    }
    /**
     * Add/edit the campaigns
     * @param Request $request
     * @return Response
     */
    public function saveCampaigns(Request $request) {
        $data = $request->all();
        $command = new SaveCampaigns($data);
        return $this->run($command, trans('Campaigns::messages.S_SaveCampaigns'), trans('Campaigns::messages.E_SaveCampaigns'));
    }
    /**
     * Campaigns dropdown
     * @param Request $request
     * @return Response
     */
    public function getCampaignsDropdown(Request $request) {
        $data = $request->all();
        $command = new GetCampaignsDropdown($data);
        return $this->run($command, trans('Campaigns::messages.S_Campaigns'), trans('Campaigns::messages.E_Campaigns'));
    }
    /**
     * Get Aprimo projects by campaigns
     * @param Request $request
     * @return Response
     */
    public function getProjects(Request $request) {
        $data = $request->all();
        $command = new GetProjectsByCampaign($data);
        return $this->run($command, trans('Campaigns::messages.S_Campaigns'), trans('Campaigns::messages.E_Campaigns'));
    }

}
