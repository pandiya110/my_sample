<?php

namespace CodePi\Roles\Executables;

use CodePi\Roles\DataSource\RolesDataSource;
use CodePi\Base\DataTransformers\DataResponse;
use URL;

class GetRolesList {

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
        $objResult = $this->dataSource->getRolesList($params);

        $arrResponse = [];
        if (!empty($objResult)) {
            foreach ($objResult as $value) {
                $arrID[$value->role_id] = $value->role_id;
                $arrResponse[$value->role_id]['id'] = $value->role_id;
                $arrResponse[$value->role_id]['name'] = $value->role_name;
                $arrResponse[$value->role_id]['description'] = $value->description;
                $arrResponse[$value->role_id]['status'] = ($value->status == '1' ) ? true : false;
            }

        }
        $response['roles'] = array_values($arrResponse);
        $response['count'] = count($arrResponse);

        if (!empty($command->page)) {

            $response['lastpage'] = $objResult->lastPage();
            $response['total'] = $objResult->totalCount;
        }

        return $response;
    }

}
