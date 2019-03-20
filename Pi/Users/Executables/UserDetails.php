<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
//use CodePi\Users\DataSource\UsersDataSource as UserDetailsDs;
use CodePi\Users\DataSource\UsersData as UsersDataDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Users\DataTranslators\UserPermissionsTransformer;
use CodePi\Users\DataTranslators\UsersBannersDevisionsTransformer;
use CodePi\Users\DataTransformers\UsersData as UsersDataTs;
class UserDetails implements iCommands { 

    private $dataSource;
    private $objDataResponse;

    function __construct() {
        $this->dataSource = new UsersDataDs ();
        $this->objDataResponse = new DataResponse (); 
    }

    function execute($command) {
        
            $params = $command->dataToArray();
            $selectedPermissionData = $this->dataSource->getUserPermissions($params);
            $objResult = $this->dataSource->getUsersDetails($params); 
            $userResponse = $this->objDataResponse->collectionFormat($objResult, new UsersDataTs(['id','name', 'image', 'email', 'firstname', 'lastname', 'department', 'status']));
            $result = array("users_info" =>  array_shift($userResponse),
                            "permissions" => $this->objDataResponse->customFormat($selectedPermissionData, new UserPermissionsTransformer));
          return $result;  
    }

}
