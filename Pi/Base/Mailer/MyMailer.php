<?php

namespace CodePi\Base\Mailer;

use CodePi\Base\Mailer\EmailController;
use CodePi\Base\Mailer\EmailDetails;

use CodePi\Base\Eloquent\Settings;


use Mail,
    Exception;
use CodePi\Base\Exceptions\EmailException;

abstract class MyMailer {

    protected $objEmailTemplate;

    function sendEmail($view, $data) {
        // $data['replyto'] = Config::get('constants.replyTo');
        // $data['replytoName'] = Config::get('constants.replyToName');
        try {
            $reseult = Mail::send($view, $data, function ($message) use ($data) {
                        // global $data;
                        $message->from($data ['from'], $data ['from_name']);
                        $message->to($data ['to_email'], $data ['to_fname'])->subject($data ['subject']);
                        if(isset($data['attachment'])){
                            $data['attachment'] = json_decode($data['attachment']);
                            $size = count($data['attachment']); //get the count of number of attachments 
                            for ($i=0; $i < $size; $i++) {
                                $message->attach($data['attachment'][$i]);
                            }          
                        }     
                        
                        // $message->replyTo($data['replyto'], $data['replytoName']);
                    });
        } catch (Exception $e) {

            throw new EmailException($e->getMessage());
        }
    }

    function send($view, $data, $ec = false) {        
        $objEmailController = new EmailController ();
        $objEmailDetails = new EmailDetails ();
        $isStopEmailOutgoing = Settings::key('stop_outgoing_emails');
        $send_date = date('Y-m-d H:i:s');
        /* email validation */
        $email = $data ['to_email'];
        $email_send = '';
        if (filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $email_send = $email;

            if (!empty($ec)) {
                $updateEmailController = array(
                    'id' => $data ['controller_id'],
                    'sent_date' => $send_date,
                    'status' => 1
                );
                if(isset($data['attachment'])){
                    $data['attachment'] = json_encode($data['attachment']);
                }

                $updateEmailControllerData = $objEmailController->updateEmailController($updateEmailController);
                $saveEmailDetails = $objEmailDetails->saveEmailDetails($data);
                $this->sendEmail($view, $data);
            }
        }
        if (empty($ec)) {
            if ($isStopEmailOutgoing == false && empty($ec)) {
                $this->sendEmail($view, $data);
                $objEmailDetails = new EmailDetails ();
                $saveEmailDetails = $objEmailDetails->saveEmailDetails($data);
            } else {
                $EmailController = $objEmailController->saveEmailController($data);
            }
        }
    }

}
