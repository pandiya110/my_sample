<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\EmailControllerDataSource;

class EmailControllerSendMail implements iCommands {

    private $dataSource;

    function __construct() {
        $this->dataSource = new EmailControllerDataSource();
    }
    /**
     * 
     * @param type $command
     * @return type
     */
    function execute($command) {

        $result = $this->dataSource->emailControllerSendMail($command);
        if ($result == 'success') {
            $result = $this->dataSource->SendMailEmailControllersData($command);
        }
        return $result;
    }

}
