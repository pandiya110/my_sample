<?php

namespace CodePi\Roles\Executables;

use CodePi\Roles\DataSource\RolesDataSource;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Roles\DataTransformers\RoleHeaderTransformers as RoleHeaderTs;
use CodePi\Roles\DataTransformers\RolePermTransformers;
class GetRoleHeaders {

    /**
     *
     * @var class 
     */
    private $dataSource;

    /**
     *
     * @var class 
     */
    private $objDataResponse;

    /**
     * 
     * @param RolesDataSource $objRoleDs
     * @param DataResponse $objDataResponse
     */
    public function __construct(RolesDataSource $objRoleDs, DataResponse $objDataResponse) {
        $this->dataSource = $objRoleDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * 
     * @param object $command
     * @return array
     */
    public function execute($command) {
        $response = [];
        $params = $command->dataToArray();
        $roleId = isset($params['roles_id']) ? $params['roles_id'] : 0;
        //$response['roles_id'] = $roleId;
        /**
         * Get Selected Role Permissions
         */
        $objPermission = $this->dataSource->getRolePermissions($command);
        $arrPermission = $this->objDataResponse->collectionFormat($objPermission, new RolePermTransformers(['system_permissions'])); 
        $response['system_permissions'] = isset($arrPermission[0]) && isset($arrPermission[0]['system_permissions']) ? $arrPermission[0]['system_permissions'] : [];
        /**
         * Get Roles mapped items grid
         */
        $objResult = $this->dataSource->getRolesHeaders($roleId);
        
        $response['headers'] = $this->objDataResponse->collectionFormat($objResult, new RoleHeaderTs([]));        
        $channel = $this->isChannelsRequire();
        $isExists =  $this->checkChannelsExistsorNot($response['headers']);       
        
        if($isExists == false){
            array_splice( $response['headers'], 3, 0, $channel );            
        }
               
        return $response;
    }
    /**
     * 
     * @return array
     */
    public function isChannelsRequire(){
        $array[] = [
                    'headers_id' => 999,
                    'alias_name' => 'Channels', 
                    'color_code_id' => 8, 
                    'column_name' => 'Channels', 
                    'isChecked' => true, 
                    'isDisable' => false, 
                    'order_no' => 4
                   ];
        return $array;
    }
    /**
     * 
     * @param array $secondArr
     * @return boolean
     */
    public function checkChannelsExistsorNot($secondArr) {
        $isExists = false;
        if (is_array($secondArr)) {

            if (in_array(999, array_column($secondArr, 'headers_id'))) {
                $isExists = true;
            }
        }
        return $isExists;
    }

}
