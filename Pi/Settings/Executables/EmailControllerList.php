<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\EmailControllerDataSource;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Settings\DataTranslators\DataControllerTransformer;

class EmailControllerList implements iCommands {

    private $dataSource;
    private $objUserTransformer;

    function __construct() {
        $this->dataSource = new EmailControllerDataSource ();
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
        $result = $this->dataSource->emailControllerData($data);
        $response['data'] = $this->objCollectionFormat->collectionFormat($result, new DataControllerTransformer());
        $response['count'] = $this->dataSource->emailControllerCount();
        return $response;
    }

}
