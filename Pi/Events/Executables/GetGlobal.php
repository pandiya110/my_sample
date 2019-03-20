<?php

namespace CodePi\Events\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Events\DataSource\EventsDataSource as EventsDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Events\DataTransformers\EventsDataTransformers as EventsTs;

/**
 * Handle the execution of get global data
 */
class GetGlobal implements iCommands { 

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of Events
     */
    function __construct(EventsDs $objEventsDs, DataResponse $objDataResponse) {
        $this->dataSource = $objEventsDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Get the Global data
     * 
     * @param object $command
     * @return type array
     */
    function execute($command) {
        
        return $this->dataSource->getGlobalData($command);
    }

}
