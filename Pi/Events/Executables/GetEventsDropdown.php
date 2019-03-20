<?php

namespace CodePi\Events\Executables;

use CodePi\Events\DataSource\EventsDataSource;
use CodePi\Base\Libraries\PiLib;
use CodePi\Events\DataTransformers\EventDropDownTransformers as EventDrpDwnTS;
use CodePi\Base\DataTransformers\DataResponse;

class GetEventsDropdown {

    /**
     *
     * @var type 
     */
    private $dataSource;

    /**
     *
     * @var type 
     */
    private $objDataResponse;

    
    public function __construct(EventsDataSource $objEventDs, DataResponse $response) {
        $this->dataSource = $objEventDs;
        $this->objDataResponse = $response;
    }

    /**
     * Get the list of campaigns
     * @param object $command
     * @return array
     */
    public function execute($command) {

        $params = $command->dataToArray();
        $result = $this->dataSource->getEventsDropdown($params);
        $response['items'] = $this->objDataResponse->collectionFormat($result, new EventDrpDwnTS(['id', 'event_name']));
//        $arrResult = [];
//        if (!empty($result)) {
//            foreach ($result as $row) {
//                $arrResult[] = ['id' => PiLib::piEncrypt($row->id),
//                                'event_name' => PiLib::filterStringDecode($row->name)
//                               ];
//            }
//        }
//        $response['items'] = $arrResult;
        
        $response['count'] = count($result);        
        if (!empty($command->page)) {
            $response['lastpage'] = $result->lastPage();
            $response['total'] = $result->totalCount;
        }
        return $response;
    }

}
