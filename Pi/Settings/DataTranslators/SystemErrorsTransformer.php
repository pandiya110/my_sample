<?php

namespace CodePi\Settings\DataTranslators;

use League\Fractal\TransformerAbstract;
use CodePi\Base\Libraries\PiLib;

//use CodePi\ImportExportLog\Eloquant\ImportExportLog;

class SystemErrorsTransformer extends TransformerAbstract {

    function transform($ImportExportLog) {
        $objPiLib=new PiLib;
        return [
            'id' => (int) $ImportExportLog->id,
            'user_name' => $ImportExportLog->fullname,
            'message' => $ImportExportLog->message,
            'status' => $ImportExportLog->status == true ? 1: 0,
//    	    'date_added' => date("M d, Y H:i A", strtotime($ImportExportLog->date_added)), 
    	    'date_added' => $objPiLib->getUserTimezoneDate($ImportExportLog->date_added, "M d, Y h:i A")
        ];
    }

}
