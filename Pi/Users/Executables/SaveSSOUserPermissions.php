<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
#use CodePi\Users\DataSource\CreateUser as CreateUserDs;
use CodePi\Users\Mailer\UsersMailer;
use CodePi\Base\DataTransformers\DataResponse;
#use CodePi\Users\DataSource\UsersDataSource as UsersListDt;
#use CodePi\Users\DataTranslators\CollectionFormat;
#use CodePi\Users\DataTranslators\UsersTransformer;
use CodePi\Base\Exceptions\EmailException;
use CodePi\Users\DataTransformers\UsersData AS UserDataTs;
use CodePi\Users\Cache\UsersCache;
use CodePi\Users\Commands\AddPermissions;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Users\DataSource\UsersData as UserDs;

/**
 * Handle the Execution of Save the users informations
 */
class SaveSSOUserPermissions implements iCommands {

    private $dataSource;
    private $objDataResponse;
    
    
    
    /**
     * @ignore It will create an object of Users
     */
    public function __construct(UserDs $objUsersDataDs, DataResponse $objDataResponse) {
        $this->dataSource = $objUsersDataDs;
        $this->objDataResponse = $objDataResponse;
        
    }

    /**
     * excution of create and update the users informations
     * @param type $command
     * @return type  $response
     */
    function execute($command) {
        
        $result = $this->dataSource->saveSSOUserPermissions($command);        
        return $result;
    }

}
