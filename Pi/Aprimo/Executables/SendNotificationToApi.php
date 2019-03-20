<?php

namespace CodePi\Api\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Api\DataSource\ApiDataSource ;

class SendNotificationToApi implements iCommands {

    private $dataSource;
    private $objCollectionFormat;

    function __construct() {
        $this->dataSource = new ApiDataSource;
        $this->objCollectionFormat = new DataResponse();
    }

    /**
     * @param object $command
     * @return array
     */
    function execute($command) {
        $data = $command->dataToArray();
//        dd($data);
        $result = $this->dataSource->sendNotificationToApi($data);

        return $result;
    }

}
