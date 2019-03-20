<?php

namespace CodePi\Events\Mailer;

use CodePi\Base\Mailer\MyMailer;
use CodePi\Base\Mailer\EmailTemplate;
use URL;
use CodePi\Base\Libraries\PiLib;

class EventsMailer extends MyMailer {
	/**
         * 
         * @param type $emailData
         */
	function eventsNotificationsMail($emailData) {
		 
		$EmailTemplate = new EmailTemplate;
		$emailTemplateArr = $EmailTemplate->emailTemplateFormat(2);
		$data = array (
                    'to_email' => $emailData['email'],
                    'to_fname' => $emailData['firstname'],
                    'from'     =>     $emailTemplateArr['from'],
                    'from_name'  => $emailTemplateArr['from_name'],
                    'subject' => $emailTemplateArr['subject'],
                    'body' => $emailTemplateArr['body'],
                    'id' => $emailData['usersid'],
	  	);
                
		$event_name = $emailData['event_name'];     
		$img_log = 'https://mcis.iviesystems.com/resources/assets/images/wmt_logo.png';		
		$arrayReplace = array('{%username%}','{%logo1%}', '{%eventname%}');
		$arrayReplaceBy = array($emailData['firstname'], $img_log, $event_name); 
		$body_text = str_replace($arrayReplace, $arrayReplaceBy, $data['body']);	
	        $data['body'] = $body_text;		
		$view = 'emails.emailcontent';
		$this->send ( $view, $data);
	}
	
}