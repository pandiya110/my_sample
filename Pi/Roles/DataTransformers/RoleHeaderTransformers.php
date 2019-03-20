<?php

namespace CodePi\Roles\DataTransformers;

use CodePi\Base\DataTransformers\PiTransformer;
use URL,
    DB;

class RoleHeaderTransformers extends PiTransformer {

    /**
     * @param object $objHeaders
     * @return array It will loop all records of Users table
     */
    function transform($objHeaders) {
        $arrResult = $this->mapColumns($objHeaders);
        $arrResult['headers_id'] = $this->checkColumnExists($objHeaders, 'headers_id');
        $arrResult['column_name'] = $this->checkColumnExists($objHeaders, 'column_name');
        $arrResult['alias_name'] = $this->checkColumnExists($objHeaders, 'alias_name');
        $arrResult['color_code_id'] = $this->checkColumnExists($objHeaders, 'color_code_id');
        $arrResult['order_no'] = $this->checkColumnExists($objHeaders, 'headers_order_no');
        $arrResult['isChecked'] = ($objHeaders->status == '1' || in_array($objHeaders->headers_id,[56,64,46,81]))  ? true : false;
        $arrResult['isDisable'] = in_array($objHeaders->headers_id,[56,64,46,81]) ? true : false;

        return $this->filterData($arrResult);
    }

}
