<?php

namespace CodePi\Events\Executables;

use CodePi\Events\DataSource\EventsDataSource as EventsDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Events\DataTransformers\EventsDataTransformers as EventsTs;

/**
 * Handle the execution of Get events list
 */
class GetEvents {

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
     * Get the list of events 
     * 
     * @param object $command
     * @return array $response
     */
    public function execute($command) {
        $arrResponse = [];
        $objResult = $this->dataSource->getEvents($command);      
        $arrResponse['items'] = $this->objDataResponse->collectionFormat($objResult, new EventsTs(['id', 'event_name', 'start_date', 'end_date', 'status_id', 
                                                                                                   'status', 'item_count', 'campaigns_id', 'campaigns_name', 
                                                                                                   'aprimo_campaign_id','loggedInUserAccess','access_type','users_id', 
                                                                                                   'created_by', 'campaigns_projects_id', 'project_name', 'aprimo_project_id']));
        $arrResponse['total_event'] = $this->dataSource->getEventsCountbyStatus($command);

        if (!empty($params['page'])) {
            $arrResponse['count'] = $objResult->total();
            $arrResponse['lastpage'] = $objResult->lastPage();
        } else {
            $arrResponse['count'] = count($objResult);
        }

        return $arrResponse;
    }

}
