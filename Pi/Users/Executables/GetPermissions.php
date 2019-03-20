<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Users\DataSource\UsersData AS UsersDataDs;
use CodePi\Users\DataTransformers\UsersData as UsersDataTs;
use CodePi\Roles\DataSource\RolesDataSource as RoleDs;
use CodePi\Roles\DataTransformers\RolePermTransformers as RolePermTs;

/**
 * Handle the execution of get the permissions list
 */
class GetPermissions implements iCommands {

    private $dataSource;
    private $objDataResponse;
    private $roleDataSource;

    /**
     * @ignore It will create an object of Users
     */
    public function __construct(UsersDataDs $objUsersDataDs, DataResponse $objDataResponse, RoleDs $objRoleDs) {
        $this->dataSource = $objUsersDataDs;
        $this->objDataResponse = $objDataResponse;
        $this->roleDataSource = $objRoleDs;
    }

    /**
     * @param object $command
     * @return array $result
     */
    public function execute($command) {
        $arrResponse = [];
        $params = $command->dataToArray();
        if (isset($params['roles_id']) && !empty($params['roles_id'])) {            
            $objResult = $this->roleDataSource->getRolePermissions($command);
            $arrResponse = $this->objDataResponse->collectionFormat($objResult, new RolePermTs(['roles_id', 'system_permissions']));
        } else {
            $objResult = $this->dataSource->getPermissions($command);
            $arrResponse = $this->objDataResponse->collectionFormat($objResult, new UsersDataTs(['users_id', 'system_permissions']));
        }
        return array_shift($arrResponse);
    }

}
