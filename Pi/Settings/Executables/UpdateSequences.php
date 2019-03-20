<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\TableSequencesList;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Settings\Commands\TableSequences;

class UpdateSequences implements iCommands {

    private $dataSource;
    private $objCollectionFormat;

    function __construct() {
        $this->dataSource = new TableSequencesList ();
        $this->objCollectionFormat = new DataResponse ();
    }
    /**
     * 
     * @param type $command
     * @return type
     */
    function execute($command) {
        $response = array();
        $data = $command->dataToArray();        
        $result = $this->dataSource->updateSequences($data);
        return CommandFactory::getCommand(new TableSequences(['sortBy' => $data['schema']]), true);
    }

}
