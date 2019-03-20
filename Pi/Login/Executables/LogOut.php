<?php

namespace CodePi\Login\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Login\DataSource\Login as LoginDs;
class LogOut implements iCommands {

    private $dataSource;
    
    function __construct() {
        $this->dataSource = new LoginDs();
    }

    function execute($command) {

        $params=$command->dataToArray();
        $this->dataSource->logout($params); 
        return array();
        
    }

}
