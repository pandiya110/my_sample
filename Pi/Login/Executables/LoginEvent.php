<?php

namespace CodePi\Login\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Login\DataSource\LoginEvent as LoginEventDs;

class LoginEvent implements iCommands {

    private $dataSource;

    function __construct(LoginEventDs $objLoginEvent) {
        $this->dataSource = $objLoginEvent;
    }

    function execute($command) {
        $this->dataSource->loggingDetails($command);
    }

}
