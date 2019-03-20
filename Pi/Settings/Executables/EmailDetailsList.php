<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\EmailDetailsList as EmailDetailsListDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Settings\DataTranslators\EmailDetailsListTransformer;

class EmailDetailsList implements iCommands {

    private $dataSource;

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

        $response = array();
        $data = $command->dataToArray();
        $result = $this->dataSource->getEmailDetailsList($data);
        $response['data'] = $this->objCollectionFormat->collectionFormat($result, new EmailDetailsListTransformer());
        $response['count'] = $this->dataSource->emailDetailsCount();
        return $response;
    }

}
