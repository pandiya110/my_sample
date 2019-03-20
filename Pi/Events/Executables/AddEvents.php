<?php

namespace CodePi\Events\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Events\DataSource\EventsDataSource as EventsDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Events\DataTransformers\EventsDataTransformers as EventsTs;
use CodePi\Channels\DataSource\ChannelsDataSource as ChannelsDs;
use CodePi\Campaigns\DataSource\CampaignsDataSource as CampaignsDs;
/**
 * Handle the execution of Events Add/Update
 */
class AddEvents implements iCommands { 

    private $dataSource;
    private $objDataResponse;
    private $objChannels;
    private $objCampaignsDs;

    /**
     * @ignore It will create an object of Events
     */
    function __construct(EventsDs $objEventsDs, DataResponse $objDataResponse, ChannelsDs $objChannels, CampaignsDs $objCampaignsDs) {
        $this->dataSource = $objEventsDs;
        $this->objDataResponse = $objDataResponse;
        $this->objChannels = $objChannels;
        $this->objCampaignsDs = $objCampaignsDs;
    }

    /**
     * Execution of Add/Update the Events
     * 
     * @param object $command
     * @return array of saved events $response
     */
//    function executeold($command) {
//        $params = $command->dataToArray();        
//        $arrResponse = [];
//        $oldCampId = 0;
//        /**
//         * Update Events date in Items level, if any start & end date updated
//         * Update aprimo details
//         */
//        if(isset($params['id']) && !empty($params['id'])){
//            $this->dataSource->updateHistoricalReferenceDate($command);
//            $oldCampId = $this->objCampaignsDs->getAssignedCampaignsIdByEvents($params['id']);
//            $this->objCampaignsDs->setAprimoDetailsInItems($params);
//        }
//        $objResult = $this->dataSource->saveEvents($command);
//        $this->dataSource->userAccessEvents($command,$objResult['id'], $objResult->created_by);        
//        $newCampId = $params['campaigns_id'];
//        $this->objCampaignsDs->assignCampaignToEvents($oldCampId, $newCampId);
//        /**
//         * Channles mapping for events
//         */
//        if(isset($params['id']) && empty($params['id'])){
//            $arrCreatedInfo = $command->getCreatedInfo();
//            $this->objChannels->saveEventsChannels($objResult->id, $arrCreatedInfo);
//        }
//       
//        /**
//         * Get the updated events
//         */
//        $command->id = $objResult->id;
//        $eventData = $this->dataSource->getEvents($command);
//        $arrResponse = $this->objDataResponse->collectionFormat($eventData, new EventsTs());
//
//        return array_shift($arrResponse);
   // }
    
   function execute($command) {
        $params = $command->dataToArray();
        $arrResponse = [];

        $objResult = $this->dataSource->saveEvents($command);
        $this->dataSource->userAccessEvents($command, $objResult['id'], $objResult->created_by);

        /**
         * Channles mapping for events
         */
        if (isset($params['id']) && empty($params['id'])) {
            $arrCreatedInfo = $command->getCreatedInfo();
            $this->objChannels->saveEventsChannels($objResult->id, $arrCreatedInfo);
        }

        $command->id = $objResult->id;
        $eventData = $this->dataSource->getEvents($command);
        $arrResponse = $this->objDataResponse->collectionFormat($eventData, new EventsTs());

        return array_shift($arrResponse);
    }

}
