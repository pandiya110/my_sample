<?php

namespace CodePi\Settings\DataTranslators;

use League\Fractal\TransformerAbstract;
#use League\Fractal\Manager;
#use League\Fractal\Resource\Collection;
#use League\Fractal\Resource\Item;
use CodePi\Settings\Eloquant\EmailController;
use CodePi\Base\Libraries\PiLib;

class SendEmailControllerTransformer extends TransformerAbstract {

    function transform(EmailController $emailController) {
        $objPiLib=new PiLib;
        return [
            // 'sent_date' =>($emailController->sent_date===NULL) ? "" : date("M d, Y H:i A", strtotime($emailController->sent_date)),
//            'sent_date' => date("M d, Y H:i A"),
            'sent_date' => $objPiLib->getUserTimezoneDate(date("M d, Y H:i A"), "M d, Y H:i A"),
            'status' => $emailController->status
        ];
    }

}
