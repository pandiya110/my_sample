<?php
namespace CodePi\ImportExportLog\Mailer;

use CodePi\Base\Mailer\MyMailer;
use CodePi\Base\Eloquent\Settings;
use URL;
class ErrorMailer extends MyMailer {
    
    function validateMail($mailData){
        $objSettings = Settings::getSettings(['error_email_id']);
        $data = array ( 
                'to_email' => $objSettings['error_email_id'],
                'to_fname' => 'POET ERROR',
                'from'     =>    'no-reply@ivieinc.com' ,
                'from_name'  => 'no-reply@ivieinc.com',
                'subject' => 'CRON ERROR',
                'body' => $mailData['message'],    
                'id' => 0
	  	);
        $data['subject'] = 'CRON ERROR'; 
        $data['body'] = "<p>".$mailData['message']."</p>";		
        $view = 'emails.emailcontent';
        $this->send ( $view, $data);
    }
	   
}
