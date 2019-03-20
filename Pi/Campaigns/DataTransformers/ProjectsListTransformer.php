<?php

namespace CodePi\Campaigns\DataTransformers;

use CodePi\Base\DataTransformers\PiTransformer;


class ProjectsListTransformer extends PiTransformer {
    /**
     * 
     * @param type $obj
     * @return type
     */
    function transform($obj) {
        $arrResult = $this->mapColumns($obj);
        $arrResult['id'] = $this->checkColumnExists($obj, 'id');
        $arrResult['campaigns_id'] = $this->checkColumnExists($obj, 'campaigns_id');
        $arrResult['project_id'] = $this->checkColumnExists($obj, 'name');
        $arrResult['activity_id'] = $this->checkColumnExists($obj, 'description');
        $arrResult['project_name'] = $this->checkColumnExists($obj, 'title');
        $arrResult['work_flow_id'] = $this->checkColumnExists($obj, 'work_flow_id');
        $arrResult['project_type_id'] = $this->checkColumnExists($obj, 'project_type_id');
        $arrResult['project_status'] = $this->checkColumnExists($obj, 'project_status');
        $arrResult['project_manager'] = $this->checkColumnExists($obj, 'project_manager');
        $arrResult['time_zone_id'] = $this->checkColumnExists($obj, 'time_zone_id');
        return $this->filterData($arrResult);
    }

}
