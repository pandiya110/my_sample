<?php

namespace CodePi\Login\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Login\DataSource\LogOutEvent as LogOutEventDs;

class LogOutEvent implements iCommands {

    private $dataSource;

    function __construct(LogOutEventDs $objLogin) {
        $this->dataSource = $objLogin;
    }
    
    function execute($command) {
        $this->dataSource->logoutDetails($command);
        return array();
    }

}
