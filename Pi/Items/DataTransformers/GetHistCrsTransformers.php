<?php

namespace CodePi\Items\DataTransformers;

use CodePi\Base\Eloquent\Events;
use CodePi\Base\DataTransformers\PiTransformer;
use CodePi\Base\Libraries\PiLib;
class GetHistCrsTransformers extends PiTransformer {


    /**
     * @param object $objData
     * @return array It will loop all records of events table
     */
    function transform($objData) {


        $arrResult = [
            'events_id' => $this->checkColumnExists($objData, 'events_id') == '' ? 0 : ($this->encrypt_id) ? PiLib::piEncrypt($objData->events_id) : $objData->events_id,
            'event_name' => $this->checkColumnExists($objData, 'event_name'),
            'start_date' => ($this->checkColumnExists($objData, 'start_date') && $objData->start_date != '0000-00-00') ? PiLib::piDate($objData->start_date, 'M d, Y') : '',
            'end_date' => ($this->checkColumnExists($objData, 'end_date') && $objData->end_date != '0000-00-00') ? PiLib::piDate($objData->end_date, 'M d, Y') : '',
        ];

        return $this->filterData($arrResult);
    }

}
