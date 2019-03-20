<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Users\DataSource\UsersData AS UsersDataDs;
use CodePi\Users\DataTransformers\UsersData as UsersDataTs;

/**
 * Handle the execution of Users creation
 */
class AddSubDepartments implements iCommands {

    private $dataSource;
    private $objDataResponse;
    /**
     * @ignore It will create an object of SyncUsers
     */
    public function __construct(UsersDataDs $objUsersDataDs, DataResponse $objDataResponse) {
        $this->dataSource = $objUsersDataDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * @param object $command
     * @return array $arrResponse
     */
    public function execute($command) {
        $arrResponse=[];
        return $this->dataSource->saveSubDepartments($command);        
        
    }

}
