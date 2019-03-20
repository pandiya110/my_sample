<?php

namespace CodePi\Export\Mailer;

use CodePi\Base\Mailer\MyMailer;
use CodePi\Base\Mailer\EmailTemplate;
use URL;
use CodePi\Base\Libraries\PiLib;

class ExportMailer extends MyMailer {

    /**
     * 
     * @param type $emailData
     */
    function exportSftpNotification($fileName) {

//		$EmailTemplate = new EmailTemplate;
//		$emailTemplateArr = $EmailTemplate->emailTemplateFormat(2);
        $data = array(
            'to_email' => 'm.chellapandian@enterpi.com',
            'to_fname' => 'Chellapandian',
            'from' => 'do_not_reply@ivieinc.com',
            'from_name' => 'Ivie',
            'subject' => 'ListBuilder File Move to SFTP',
            'body' => $fileName.' File has been moved to sftp folder successfully',
            'id' => 1
        );

        //$img_log = 'https://mcis.iviesystems.com/resources/assets/images/wmt_logo.png';		
        //$arrayReplace = array('{%username%}','{%logo1%}', '{%eventname%}');
        //$arrayReplaceBy = array($emailData['firstname'], $img_log, $event_name); 
        //$body_text = str_replace($arrayReplace, $arrayReplaceBy, $data['body']);	
        //$data['body'] = $body_text;		
        $view = 'emails.emailcontent';
        $this->send($view, $data);
    }

}
