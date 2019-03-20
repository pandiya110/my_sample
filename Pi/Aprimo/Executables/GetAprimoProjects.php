<?php

namespace CodePi\Aprimo\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Aprimo\DataSource\AprimoDataSource;

class GetAprimoProjects implements iCommands {

    private $dataSource;

    function __construct() {
        $this->dataSource = new AprimoDataSource();
    }

    /**
     * @param object $command
     * @return array
     */
    function execute($command) {
        $data = $command->dataToArray();
        return $this->dataSource->getAprimoProjects($data);
    }

}
