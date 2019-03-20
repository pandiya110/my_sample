<?php

namespace CodePi\Aprimo\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Api\DataSource\ApiDataSource;
use CodePi\Aprimo\DataSource\AprimoDataSource;

class UpdateAprimoAuthDetails implements iCommands {

    private $dataSource;
    private $objCollectionFormat;

    function __construct() {
        $this->dataSource = new AprimoDataSource;
        $this->objCollectionFormat = new DataResponse();
    }

    /**
     * @param object $command
     * @return array
     */
    function execute($command) {
        $data = $command->dataToArray();
        $result = $this->dataSource->updateAprimoAuthDetails($data);
        return $result;
    }

}
