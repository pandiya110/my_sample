<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Users\DataSource\UsersData AS UsersDataDs;
use CodePi\Users\DataTransformers\UsersData as UsersDataTs;
use CodePi\Users\Cache\UsersCache;

class ResendActivationLink implements iCommands {

    private $dataSource;
    private $objDataResponse;
    /**
     * 
     * @param UsersDataDs $objUsersDataDs
     * @param DataResponse $objDataResponse
     */
    public function __construct(UsersDataDs $objUsersDataDs, DataResponse $objDataResponse) {
        $this->dataSource = $objUsersDataDs;
        $this->objDataResponse = $objDataResponse;
    }
    /**
     * 
     * @param obj $command
     * @return array
     */
    public function execute($command) {

        $params = $command->dataToArray();
        return $this->dataSource->resendActivationLink($params);
    }

}
