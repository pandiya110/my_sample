<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Settings\DataSource\Settings as SettingsDs;

class GetGeneralSettings implements iCommands {

    private $dataSource;

    function __construct() {
        $this->dataSource = new SettingsDs ();
    }
    /**
     * 
     * @param type $command
     * @return type
     */
    function execute($command) {
        $result = $this->dataSource->getGeneralSettings($command);
        return $result;
    }

}
