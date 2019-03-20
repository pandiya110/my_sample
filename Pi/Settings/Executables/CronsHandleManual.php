<?php

namespace CodePi\Settings\Executables;

use CodePi\Base\Commands\iCommands;
#use CodePi\Cron\DataSource\CronsList as CronListcdm;
use CodePi\Settings\DataSource\ListCronsDataSource as CronListDs;

class CronsHandleManual implements iCommands {

    private $dataSource;

    /**
     * @author Enterpi
     * @ignore It will create an object of 
     */
    public function __construct(CronListDs $objCronDs) {
        $this->dataSource = $objCronDs;
    }

    /**
     * @param object $command     
     */
    public function execute($command) {        
        $result = $this->dataSource->cronsHandleManual($command);
        return $result;
    }

}
