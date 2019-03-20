<?php

namespace CodePi\Events\DataTransformers;

use CodePi\Base\Eloquent\Events;
use CodePi\Base\DataTransformers\PiTransformer;
use CodePi\Base\Libraries\PiLib;

class EventDropDownTransformers extends PiTransformer {

    public $defaultColumns = ['id', 'event_name'];
    public $encryptColoumns = [];

    /**
     * 
     * @param type $row
     * @return type
     */
    function transform($row) {

        $arrResult = $this->mapColumns($row);
        $arrResult['id'] = PiLib::piEncrypt($row->id);
        $arrResult['event_name'] = PiLib::filterStringDecode($row->name);
        return $this->filterData($arrResult);
    }

}
