<?php

namespace CodePi\Settings\Mailer;

use CodePi\Base\Mailer\MyMailer;
use CodePi\Base\Mailer\EmailTemplate;
use Crypt,
    URL;
use Request;
use CodePi\Settings\Eloquant\EmailController;

class DataControllerMailer extends MyMailer {

    function dataControllerSendMail($data) {
        print_r($data);
        die();
        foreach ($data['id'] as $key => $value) {
            echo 10022;
        }
// 		       $objEmailcontroller = new EmailController;
// 		       $userData=$objEmailcontroller->find(6);
// 				$EmailTemplate = new EmailTemplate;
// 				$emailTemplateArr = $EmailTemplate->emailTemplateFormat(1);
// 					$data = array (
// 			  			'to_email' => $userData['to'],
// 			  			'from'     =>     $userData['from'],
// 			  			'from_name'  => $emailTemplateArr['from_name'],
// 			  			'subject' => $emailTemplateArr['subject'],
// 			  			'body' => $emailTemplateArr['body'],
// 						'id' => 1,
// 						'controller_id' => $userData['id'],
// 			  	); 
// 			    $view = 'emails.emailcontent';   
// 				return $this->send ( $view, $data,$ec=true);
    }

}
