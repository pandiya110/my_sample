<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\ListCronsDataSource;
use CodePi\Base\DataTransformers\DataResponse;

class ListCrons implements iCommands {

    private $dataSource;
    private $objCollectionFormat;

    function __construct() {
        $this->dataSource = new ListCronsDataSource ();
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
        $result = $this->dataSource->listCrons($data);
        return $result;
    }

}
