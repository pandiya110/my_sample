<?php

namespace CodePi\Login\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Login\DataSource\Login as LoginDs;

class SetAuthToken implements iCommands {

    private $dataSource;

    function __construct() {
        $this->dataSource = new LoginDs ();
    }
    /**
     * Execution of creation of Auth Token creations
     * 
     * @param object $command
     * @return string
     */
    function execute($command) {
        $params = $command->dataToArray();
        $response = $this->dataSource->setAuthToken($params);
        return $response;
    }

}
