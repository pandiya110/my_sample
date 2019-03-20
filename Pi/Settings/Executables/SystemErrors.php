<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\SystemErrorsLogsList;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Settings\DataTranslators\SystemErrorsTransformer;

class SystemErrors implements iCommands {

    private $dataSource;
    private $objUserTransformer;

    function __construct() {
        $this->dataSource = new SystemErrorsLogsList ();
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
        $result = $this->dataSource->systemErrorsData($data);
        $response['data'] = $this->objCollectionFormat->collectionFormat($result, new SystemErrorsTransformer());
        $response['count'] = $result->total();
        return $response;
    }

}
