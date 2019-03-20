<?php

namespace CodePi\Roles\Executables;

use CodePi\Roles\DataSource\RolesDataSource;
use CodePi\Base\DataTransformers\DataResponse;
use URL;

/**
 * Handle the execution of get department list
 */
class GetRolesDetails {

    private $dataSource;
    private $objDataResponse;

    public function __construct(RolesDataSource $objRoleDs, DataResponse $objDataResponse) {
        $this->dataSource = $objRoleDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * 
     * @param type $command
     * @return type
     */
    public function execute($command) {
        $response = [];
        $objResult = $this->dataSource->getRolesDetails($command);
        
        $arrID = $arrResponse = [];        
        if (!empty($objResult)) {
            foreach ($objResult as $value) {
                $arrID[$value->role_id] = $value->role_id;
                $arrResponse[$value->role_id]['id'] = $value->role_id;
                $arrResponse[$value->role_id]['name'] = $value->role_name;
                $arrResponse[$value->role_id]['description'] = $value->description;
                $arrResponse[$value->role_id]['status'] = ($value->status == '1' ) ? true : false;
               
                if ($value->role_header_id != '') {
                    $arrChild[$value->role_id]['items_headers'][] = ['id' => $value->role_header_id, 
                                                                     'alias_name' => $value->headers_alias_name, 
                                                                     'color_code_id' => $value->color_id,                                                                      
                                                                     'headers_id' => $value->headers_id, 
                                                                     'order_no' => $value->headers_order_no
                                                                    ];
                }
            }
            foreach ($arrID as $key) {
                $arrResponse[$key]['items_headers'] = isset($arrChild[$key]) && isset($arrChild[$key]['items_headers']) ? ($arrChild[$key]['items_headers']) : array();
            }
        }
        $response['roles'] = array_values($arrResponse);
        return $response;
    }

}
