<?php

namespace CodePi\Settings\DataTranslators;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\DataTransformers\PiTransformer;

class DataControllerTransformer extends PiTransformer {

    public $defaultColumns = ['id', 'from', 'to', 'subject', 'date_added', 'sent_date', 'message', 'status', 'gt_date_added', 'last_modified'];
    public $encryptColoumns = [];

    /**
     * @param object $objResult
     * @return array It will loop all records of email_controller table
     */
    function transform($objResult) {

        $objPiLib = new PiLib;
        $arrResult = $this->mapColumns($objResult);
//        $arrResult['date_added'] = empty(PiLib::piIsset($arrResult, 'gt_date_added', '')) ? '' : PiLib::piDate((new PiLib())->getTimezoneDate($arrResult['date_added'], -250), 'M d, Y H:i A');
        $arrResult['date_added'] = empty(PiLib::piIsset($arrResult, 'date_added', '')) ? '' : $objPiLib->getUserTimezoneDate($arrResult['last_modified'], 'M d, Y h:i A');
        return $this->filterData($arrResult);

    }

}
