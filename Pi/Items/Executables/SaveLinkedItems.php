<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\LinkedItemsDataSource as LinkedItemDs; 
use CodePi\Base\DataTransformers\DataResponse;


/**
 * Handle the execution of linked Items creation
 */
class SaveLinkedItems implements iCommands { 

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of linked Items
     */
    function __construct(LinkedItemDs $objLinkedItemDs, DataResponse $objDataResponse) {
        $this->dataSource = $objLinkedItemDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Execution of save linked items
     * 
     * @param object $command
     * @return array $result
     */
    function execute($command) {

        //$params = $command->dataToArray ();                
        $result = $this->dataSource->saveLinkedItems($command);
        return $result;
    }

}
