<?php

namespace CodePi\Campaigns\DataTransformers;

use CodePi\Base\DataTransformers\PiTransformer;
use CodePi\Base\Libraries\PiLib;

class CampaignsListTransformer extends PiTransformer {

    function transform($obj) {
        $arrResult = $this->mapColumns($obj);
        $arrResult['id'] = $this->checkColumnExists($obj, 'id');
        $arrResult['campaigns_id'] = $this->checkColumnExists($obj, 'id');
        $arrResult['name'] = $this->checkColumnExists($obj, 'name');
        $arrResult['description'] = $this->checkColumnExists($obj, 'description');
        $arrResult['status'] = ($obj->status == '1') ? true : false;
        $arrResult['start_date'] = PiLib::piDate($obj->start_date, 'Y-m-d');
        $arrResult['end_date'] = PiLib::piDate($obj->end_date, 'Y-m-d');
        $arrResult['assign_status'] = ($obj->assign_status == '1') ? true : false;
        $arrResult['aprimo_campaign_id'] = $this->checkColumnExists($obj, 'aprimo_campaign_id');
        $arrResult['campaigns_name'] = $this->checkColumnExists($obj, 'name').'-'.$this->checkColumnExists($obj, 'aprimo_campaign_id') ;
        
        return $this->filterData($arrResult);
    }

}
