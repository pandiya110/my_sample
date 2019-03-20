<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Users\DataSource\UsersData AS UsersDataDs;
use CodePi\Users\DataTransformers\UsersData as UsersDataTs;

/**
 * Handle the execution of get global data
 */
class GetGlobalData implements iCommands {

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of users
     */
    public function __construct(UsersDataDs $objUsersDataDs, DataResponse $objDataResponse) {
        $this->dataSource = $objUsersDataDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * @param object $command
     * @return array $result
     */
    public function execute($command) {
        $arrResponse = [];
        $objResult = $this->dataSource->getGlobalData($command);
        $arrResponse = $this->objDataResponse->collectionFormat($objResult, new UsersDataTs(['global_system_permissions', 'global_system_roles', 'global_nonvisible_columns']));
        return array_shift($arrResponse);
    }

}
