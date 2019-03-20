<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\UsersLogsList as UsersLogsListDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Settings\DataTranslators\UsersLogsListTransformer;

class UsersLogsList implements iCommands {

    private $dataSource;
    private $objCollectionFormat;

    function __construct() {
        $this->dataSource = new UsersLogsListDs();
        $this->objCollectionFormat = new DataResponse();
    }
    /**
     * 
     * @param type $command
     * @return type
     */
    function execute($command) {
        $response['data'] = $this->dataSource->getUsersLogs($command);
        //$response['data'] = $this->objCollectionFormat->collectionFormat($result, new UsersLogsListTransformer());
        $response['count'] = $this->dataSource->getLogsCount();
        return $response;
    }

}
