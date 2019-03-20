<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\EmailControllerDataSource;

class EmailControllerDetails implements iCommands {

    private $dataSource;

    function __construct() {
        $this->dataSource = new EmailControllerDataSource ();
    }
    /**
     * 
     * @param type $command
     * @return type
     */
    function execute($command) {
        $controllerid = $command->id;
        $EmailControllerdata = $this->dataSource->getEmailControllerdata($controllerid);
        return $EmailControllerdata;
    }

}
