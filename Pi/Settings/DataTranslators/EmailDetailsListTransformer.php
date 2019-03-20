<?php

namespace CodePi\Settings\DataTranslators;

#use CodePi\Base\Eloquent\EmailDetails;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\DataTransformers\PiTransformer;


class EmailDetailsListTransformer extends PiTransformer {

    public $defaultColumns = ['id', 'from', 'to', 'subject', 'date_added', 'sent_date', 'message', 'status'];
  
    public $encryptColoumns = [];

    /**
    * @param object $objResult
    * @return array It will loop all records of email_details table
    */
    function transform($objResult) { 
        $objPiLib=new PiLib;
       $arrResult = $this->mapColumns($objResult);
       $arrResult['sent_date'] = $objPiLib->getUserTimezoneDate($objResult['date_added'], 'M d, Y h:i A');
       // $arrResult['sent_date'] = $objPiLib->getUserTimezoneDate($objResult['sent_date'], 'M d, Y h:i A');
       // $arrResult['sent_date'] = date('M d, Y h:i A',strtotime($objResult->date_added));
       //$arrResult['id'] = empty(PiLib::piIsset($arrResult, 'id', '')) ? '' :  PiLib::piDecrypt($arrResult['id']);
       // print_r($arrResult);die;
       return $this->filterData($arrResult);
    }

    // function transform(EmailDetails $emailDetails) {
    //     $objPiLib=new PiLib;
    //     return [
    //         'id' => (int) $emailDetails->id, 
    //         'from' => $emailDetails->from,
    //         'to' => $emailDetails->to,
    // 	    'subject' => $emailDetails->subject,
    // 	    'date_added' => ($emailDetails->gt_date_added ===NULL) ? "" :$objPiLib->getTimezoneDate($emailDetails->gt_date_added,session('timezone')), 
    // 	    // 'sent_date' => ($emailDetails->sent_date===NULL) ? "" : date("M d, Y H:i A", strtotime($emailDetails->sent_date)),
    // 	    // 'status' => $emailDetails->status,
    //     ];
    // }

}
