<?php

namespace CodePi\Roles\DataTransformers;
use CodePi\Base\DataTransformers\PiTransformer;

use URL,
    DB;

class RolePermTransformers extends PiTransformer {

    public $defaultColumns = ['roles_id', 'system_permissions'];
    public $encryptColoumns = [];

    /**
     * @param object $objRoles
     * @return array It will loop all records of Users table
     */
    function transform($objRoles) {

        if (!empty($objRoles)) {

            $arrResult = $this->mapColumns($objRoles);
            $arrResult['roles_id'] = $this->checkColumnExists($objRoles, 'id');
            return $this->filterData($arrResult);
        } else {
            return [];
        }
    }

}
