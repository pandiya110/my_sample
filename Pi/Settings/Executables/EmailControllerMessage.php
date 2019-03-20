<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\EmailControllerDataSource as EmailControllerDataSourceDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Settings\DataTranslators\DataControllerTransformer;

class EmailControllerMessage implements iCommands {

    private $dataSource;
    private $objCollectionFormat;

    function __construct() {
        $this->dataSource = new EmailControllerDataSourceDs();
        $this->objCollectionFormat = new DataResponse();
    }
    /**
     * 
     * @param type $command
     * @return type
     */
    function execute($command) {
        $result = $this->dataSource->getEmailControllerMessage($command);
        $response = $this->objCollectionFormat->collectionFormat($result, new DataControllerTransformer(['message']));
        return $response;
    }

}
