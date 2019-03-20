<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\EmailDetailsList as EmailDetailsListDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Settings\DataTranslators\EmailDetailsListTransformer;

class EmailDetailsMessage implements iCommands {

    private $dataSource;
    private $objCollectionFormat;

    function __construct() {
        $this->dataSource = new EmailDetailsListDs();
        $this->objCollectionFormat = new DataResponse();
    }
    /**
     * 
     * @param type $command
     * @return type
     */
    function execute($command) {
        $result = $this->dataSource->getEmailDetailsMessage($command);
        $response = $this->objCollectionFormat->collectionFormat($result, new EmailDetailsListTransformer(['message']));
        return $response;
    }

}
