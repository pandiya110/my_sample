<?php

namespace CodePi\Roles\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Roles\DataSource\RolesDataSource as RoleDs;
use CodePi\Roles\DataTransformers\RolePermTransformers;

class AddRolePermissions implements iCommands {

    private $dataSource;
    private $objDataResponse;
    /**
     * 
     * @param RoleDs $objRoleDs
     * @param DataResponse $objDataResponse
     */
    public function __construct(RoleDs $objRoleDs, DataResponse $objDataResponse) {
        $this->dataSource = $objRoleDs;
        $this->objDataResponse = $objDataResponse;
    }
    /**
     * 
     * @param obj $command
     * @return array
     */
    public function execute($command) {
        $arrResponse = [];
        $this->dataSource->saveRolePermissions($command);
        $objResult = $this->dataSource->getRolePermissions($command);
        $arrResponse = $this->objDataResponse->collectionFormat($objResult, new RolePermTransformers(['roles_id', 'system_permissions']));                 
        return array_shift($arrResponse);
    }

}
