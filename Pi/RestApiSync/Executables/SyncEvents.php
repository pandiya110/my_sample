<?php

namespace CodePi\RestApiSync\Executables;

use CodePi\RestApiSync\DataSource\EventsDataSource as EventsDS;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\RestApiSync\DataTransformers\EventsTransformer as EventsTs;
use CodePi\Base\DataSource\Elastic;
use CodePi\Base\Commands\CommandFactory;

/**
 * Handle the execution of get department list
 */
class SyncEvents {

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of Departments
     */
    public function __construct(EventsDS $objEventsDS, DataResponse $objDataResponse) {
        $this->dataSource = $objEventsDS;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Execution of Get the list of departments
     * @param object $command
     * @return array $response
     */
    public function execute($command) {
        return $this->dataSource->syncDataToElastic($command);
    }

}
