<?php

namespace CodePi\Channels\Http\Controllers;

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
use CodePi\Channels\Commands\AddChannels;
use CodePi\Channels\Commands\GetChannelsList;
use CodePi\Channels\Commands\GetChannelDetails;
use CodePi\Channels\Commands\UploadChannelLogo;
use CodePi\Channels\Commands\SaveItemsChannels;
use CodePi\Channels\Commands\GetChannelsAdtypes;

class ChannelsController extends PiController {

    /**
     * Add/Update Channels and Adtypes
     * @param Request $request
     * @return Response
     */
    public function addChannels(Request $request) {
        $data = $request->all();
        $command = new AddChannels($data);
        return $this->run($command, trans('Channels::messages.S_AddChannels'), trans('Channels::messages.E_AddChannels'));
    }

    /**
     * Get list of channels
     * @param Request $request
     * @return Response
     */
    public function getChannelsList(Request $request) {
        $data = $request->all();
        $command = new GetChannelsList($data);
        return $this->run($command, trans('Channels::messages.S_GetChannels'), trans('Channels::messages.E_GetChannels'));
    }

    /**
     * Get channels details
     * @param Request $request
     * @return Response
     */
    public function getChannelDetails(Request $request) {
        $data = $request->all();
        $command = new GetChannelDetails($data);
        return $this->run($command, trans('Channels::messages.S_GetChannelsDetails'), trans('Channels::messages.E_GetChannelsDetails'));
    }

    /**
     * Upload channels logo
     * @param Request $request
     * @return Response
     */
    public function uploadChannelLogo(Request $request) {
        $data = $request->all();
        $command = new UploadChannelLogo($data);
        return $this->run($command, trans('Channels::messages.S_UploadLogo'), trans('Channels::messages.E_UploadLogo'));
    }

    /**
     * Save Items channels
     * @param Request $request
     * @return Response
     */
    public function saveItemsChannels(Request $request) {
        $data = $request->all();
        $command = new SaveItemsChannels($data);
        return $this->run($command, trans('Channels::messages.S_SaveItemsChannels'), trans('Channels::messages.E_SaveItemsChannels'));
    }

    /**
     * Get Channels Adtypes
     * @param Request $request
     * @return Response
     */
    public function getChannelsAdtypes(Request $request) {
        $data = $request->all();
        $command = new GetChannelsAdtypes($data);
        return $this->run($command, trans('Channels::messages.S_ChannelAdtypes'), trans('Channels::messages.E_ChannelAdtypes'));
    }

}
