<?php

namespace CodePi\RestApiSync\Executables;

use CodePi\RestApiSync\DataSource\EventsDataSource as EventsDS;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\RestApiSync\DataTransformers\EventsTransformer as EventsTs;
use CodePi\Base\DataSource\Elastic;

class GetEvents {

    private $dataSource;
    private $objDataResponse;

    public function __construct(EventsDS $objEventsDS, DataResponse $objDataResponse) {
        $this->dataSource = $objEventsDS;
        $this->objDataResponse = $objDataResponse;
    }

    public function execute($command) {
        $response = [];
        $objElasic = new Elastic;
        /**
         * Clear the data first in Elastic search
         */
        $this->dataSource->clearDataInEsearch('sm_events', 'events');        
        $objResult = $this->dataSource->getEvents($command);
        $result = $this->objDataResponse->collectionFormat($objResult, new EventsTs([]));
        
        if (!empty($result)) {
            foreach ($result as $l => $m) {

                $objE = [];
                $objE['index'] = 'sm_events';
                $objE['type'] = 'events';
                $objE['id'] = $m['id'];
                $objE['body'] = $m;
                $objElasic->insert($objE);
            }
        }
        return $result;
    }

}
