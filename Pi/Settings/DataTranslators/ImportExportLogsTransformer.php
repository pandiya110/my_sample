<?php

namespace CodePi\Settings\DataTranslators;

use League\Fractal\TransformerAbstract;
use CodePi\Base\Libraries\PiLib;

//use CodePi\ImportExportLog\Eloquant\ImportExportLog;

class ImportExportLogsTransformer extends TransformerAbstract {

    function transform($ImportExportLog) {
        $objPiLib=new PiLib;
        return [
            'id' => (int) $ImportExportLog->id,
            'user_name' => $ImportExportLog->fullname,
            'action' => $ImportExportLog->action,
    	    'filename' => $ImportExportLog->filename,
            'params' => $ImportExportLog->params,
            'message' => $ImportExportLog->message,
            'response' => $ImportExportLog->response,
            'process_status' => $ImportExportLog->process_status,
            'master_info' => $ImportExportLog->master_info,
   	        'date_added' => date("M d, Y H:i A", strtotime($ImportExportLog->date_added)), 
    	    // 'date_added' => empty($ImportExportLog->date_added) ? '' : $objPiLib->getUserTimezoneDate($ImportExportLog->date_added, "M d, Y h:i A")
        ];
    }

}
