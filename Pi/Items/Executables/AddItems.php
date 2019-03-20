<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs; 
use CodePi\Base\DataTransformers\DataResponse;
use App\Events\ItemActions;
use App\Events\IqsProgress;
#use CodePi\Events\DataTransformers\EventsDataTransformers as EventsTs;

/**
 * Handle the execution of Event Items creation
 */
class AddItems implements iCommands { 

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of Events Items
     */
    function __construct(ItemDs $objItemDs, DataResponse $objDataResponse) {
        $this->dataSource = $objItemDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * @param object $command
     * @return array of saved items $response
     */
    function execute($command) {

        //$params = $command->dataToArray ();
        $objResult = $this->dataSource->saveItems($command);
        
        
        return $objResult;
    }

}
