<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Users\DataSource\ResetPassword;


use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Users\DataTransformers\UsersData as UsersDataTs;

/**
 * Handle the execution of Users creation
 */
class ValidateAccountToken implements iCommands {

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of SyncUsers
     */
    public function __construct(ResetPassword $objAccountDS, DataResponse $objDataResponse) {
        $this->dataSource = $objAccountDS;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * @param object $command
     * @return arrau $result
     */
    public function execute($command) {

        $objResult = $this->dataSource->validateAccountToken($command);
        //$response = $this->objDataResponse->collectionFormat($objResult, new UsersDataTs(['id', 'name', 'image', 'email', 'user_from', 'firstname', 'lastname', 'status']));
        return $objResult;
        //return $result;
    }

}
