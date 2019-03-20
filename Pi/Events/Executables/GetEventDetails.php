<?php

namespace CodePi\Events\Executables;

use CodePi\Events\DataSource\EventsDataSource as EventsDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Events\DataTransformers\EventsDataTransformers as EventsTs;

/**
 * Handle the executions of get events details
 */
class GetEventDetails {

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of ListEvents
     */
    public function __construct(EventsDs $objEventsDs, DataResponse $objDataResponse) {
        $this->dataSource = $objEventsDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Get the events details
     * 
     * @param object $command->id
     * @return array $arrResponse
     */
    public function execute($command) {

        $arrResponse = [];        
        $objResult = $this->dataSource->getEventDetails($command);        
        $arrResponse['events'] = $this->objDataResponse->collectionFormat($objResult, new EventsTs(['id', 'event_name', 'start_date', 
                                                                                                    'end_date', 'status_id', 'status', 'item_count', 'campaigns_id', 
                                                                                                    'campaigns_name', 'aprimo_campaign_id','access_type','users_id', 
                                                                                                    'created_by', 'campaigns_projects_id', 'project_name', 'aprimo_project_id']));

        if (count($objResult) > 0) {
            $arrResponse['status'] = true;
        } else {
            $arrResponse['status'] = false;
        }
        return $arrResponse;
    }

}
