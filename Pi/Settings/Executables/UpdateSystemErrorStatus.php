<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\SystemErrorsLogsList;
use CodePi\Base\DataTransformers\DataResponse;

class UpdateSystemErrorStatus implements iCommands {

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
        $data = $command->dataToArray();
        $result = $this->dataSource->updateSystemErrorlogsStatus($data);
        return $result;
    }

}
