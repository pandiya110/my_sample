<?php

namespace CodePi\Templates\DataTransformers;

use CodePi\Base\DataTransformers\PiTransformer;
use CodePi\Base\Libraries\PiLib;

class TemplateListTransformer extends PiTransformer {

    function transform($obj) {
        $arrResult = $this->mapColumns($obj);
        $arrResult['id'] = $this->checkColumnExists($obj, 'id');
        $arrResult['name'] = $this->checkColumnExists($obj, 'name');
        $arrResult['is_active'] = !empty($obj->is_active) ? true : false;
        return $this->filterData($arrResult);
    }

}
