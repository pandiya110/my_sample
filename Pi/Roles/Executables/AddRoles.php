<?php

namespace CodePi\Roles\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Roles\DataSource\RolesDataSource;
use CodePi\Roles\Commands\GetRolesDetails;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Roles\Commands\AddRolePermissions;
use CodePi\Roles\Commands\GetRoleHeaders;

class AddRoles implements iCommands {

    private $dataSource;
    private $objDataResponse;

    function __construct(RolesDataSource $objRoleDS, DataResponse $objDataResponse) {
        $this->dataSource = $objRoleDS;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Executions of Save Roles and role header level permissions
     * @param object $command
     * @return array
     */
    function execute($command) {

        $id = '';
        $params = $command->dataToArray();
        if (isset($params['id']) && empty($params['id'])) {
            $objResult = $this->dataSource->saveRoles($command);
            $id = $objResult->id;
        } else {
            $id = $params['id'];
        }

        if (!empty($id)) {
            $command->id = $id;
            $this->dataSource->saveRoleItemsHeaders($command);

            if (isset($params['permissions']) && !empty($params['permissions'])) {
                $permissionData = ['roles_id' => $params['id'], 'permissions' => $params['permissions']];
                $objCmd = new AddRolePermissions($permissionData);
                $savePermissions = CommandFactory::getCommand($objCmd, true);
            }

            $objCommand = new GetRolesDetails(['id' => $id]);
            $objRes = CommandFactory::getCommand($objCommand);
            $RoleDetails = array_shift($objRes['roles']);
            
            unset($RoleDetails['items_headers']);
            $objCommand = new GetRoleHeaders(['id' => $id]);
            $response = CommandFactory::getCommand($objCommand);
            $response = array_merge($RoleDetails, $response);
            
            return $response;
        }
    }

}
