<?php

namespace CodePi\Users\Mailer;

use CodePi\Base\Mailer\MyMailer;
use CodePi\Base\Mailer\EmailTemplate;
use CodePi\Base\Mailer\UserForgotTokens;
use Crypt,
    URL,
    Request;
use CodePi\Base\Libraries\PiLib;

class UsersMailer extends MyMailer {

    /**
     * use Crypt md5 url
     * When we created new user the registration email is Sending email from here..
     * @Params:EmailId,Username,'FromEmailId' and body of emails are coming from database table email_template
     */
    function registrationEmail($userData) {        
        $EmailTemplate = new EmailTemplate;
        $emailTemplateArr = $EmailTemplate->emailTemplateFormat(1);
        $data = array(
            'to_email' => $userData->email,
            'to_fname' => $userData->firstname,
            'from' => $emailTemplateArr['from'],
            'from_name' => $emailTemplateArr['from_name'],
            'subject' => $emailTemplateArr['subject'],
            'body' => $emailTemplateArr['body'],
            'id' => $userData->id,
        );
        $id = $userData->id;
        $token = md5(time());
        $enc_id = PiLib::piEncrypt($id); //Crypt::encrypt ( $id );
        //$site_url = URL::to ( 'getActivationLink/' . $enc_id . '/' . $token.'/a' );      		
        $site_url = URL::to('createPassword?id=' . $enc_id . '&token=' . $token . '&tp=ac');
        $img_log = URL::to('') . '/resources/assets/images/wmt_logo.png'; //URL::to('') .'/resources/views/fav.png';
        $imglog = URL::to('') . '/resources/assets/images/activation-img.png'; //URL::to('') .'/resources/views/fav.png';
        $fwbtn = URL::to('') . '/resources/views/fav.png';
        $imgarrow = URL::to('') . '/resources/assets/images/arrow.png';
        $arrayReplace = array('{%username%}', '{%url%}', '{%logo1%}', '{%logo2%}', '{%$fwbtn%}', '{%arrow%}');
        $arrayReplaceBy = array($userData->firstname, $site_url, $img_log, $imglog, $fwbtn, $imgarrow);
        $body_text = str_replace($arrayReplace, $arrayReplaceBy, $data['body']);
        $data['body'] = $body_text;
        $objUserForgotTokens = new UserForgotTokens ();
        $saveUsersForgotTokens = $objUserForgotTokens->usersForgotTokens ($id, $token );		
        $view = 'emails.emailcontent';
        $this->send($view, $data);
    }

    /**
     * use Crypt md5 url
     * Request for forgot password is Sending email from here..
     * @Params:EmailId,Username,'FromEmailId' and body of emails are coming from database table email_template
     */
    function forgotPasswordEmail($userData) {

        $EmailTemplate = new EmailTemplate;
        $emailTemplateArr = $EmailTemplate->emailTemplateFormat(3);
        $data = array(
            'to_email' => $userData->email,
            'to_fname' => $userData->firstname,
            'from' => $emailTemplateArr['from'],
            'from_name' => $emailTemplateArr['from_name'],
            'subject' => $emailTemplateArr['subject'],
            'body' => $emailTemplateArr['body'],
            'id' => $userData->id,
        );

        $id = $userData->id;
        $token = md5(time());
        $enc_id = PiLib::piEncrypt($id);
        $site_url = URL::to('createPassword?id=' . $enc_id . '&token=' . $token . '&tp=rs');
        $img_log = URL::to('') . '/resources/assets/images/wmt_logo.png';
        $imgarrow = URL::to('') . '/resources/assets/images/arrow.png';
        $arrayReplace = array('{%username%}', '{%url%}', '{%logo1%}', '{%arrow%}');
        $arrayReplaceBy = array($userData->firstname, $site_url, $img_log, $imgarrow);
        $body_text = str_replace($arrayReplace, $arrayReplaceBy, $data['body']);
        $data['body'] = $body_text;
        $objUserForgotTokens = new UserForgotTokens ();
        $saveUsersForgotTokens = $objUserForgotTokens->usersForgotTokens($id, $token);
        $view = 'emails.emailcontent';
        $this->send($view, $data);
    }

    /**
     * 
     * When we change password then Sending sccucess email from here..
     * @Params:EmailId,Username,'FromEmailId' and body of emails are coming from database table email_template
     */
    function changePasswordEmail($userData) {
        $EmailTemplate = new EmailTemplate;
        $emailTemplateArr = $EmailTemplate->emailTemplateFormat(4);

        $name = $userData->firstname;
        $data = array(
            'to_email' => $userData->email,
            'to_fname' => $userData->firstname,
            'from' => $emailTemplateArr['from'],
            'from_name' => $emailTemplateArr['from_name'],
            'subject' => $emailTemplateArr['subject'],
            'body' => $emailTemplateArr['body'],
            'id' => $userData->id,
        );

        $site_url = url('login');
        $img_log = URL::to('') . '/resources/assets/images/wmt_logo.png';
        $arrayReplace = array('{%username%}', '{%url%}', '{%logo1%}', '{%email%}');
        $arrayReplaceBy = [$name, $site_url, $img_log, $userData->email];
        $body_text = str_replace($arrayReplace, $arrayReplaceBy, $data['body']);
        $data['body'] = $body_text;
        $view = 'emails.emailcontent';
        $this->send($view, $data);
    }

}
