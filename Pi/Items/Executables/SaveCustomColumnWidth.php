<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\UsersColumnWidthDS;

class SaveCustomColumnWidth implements iCommands {

    /**
     *
     * @var class
     */
    private $dataSource;

    function __construct(UsersColumnWidthDS $objUsersColumnWidthDs) {
        $this->dataSource = $objUsersColumnWidthDs;
    }

    /**
     * 
     * @param obj $command
     * @return array
     */
    function execute($command) {

        $result = $this->dataSource->saveCustomColumnWidthByUsers($command);
        return $result;
    }

}
