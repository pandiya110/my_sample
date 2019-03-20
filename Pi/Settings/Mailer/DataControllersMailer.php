<?php

namespace CodePi\Settings\Mailer;

use CodePi\Base\Mailer\MyMailer;
use CodePi\Settings\Mailer\EmailTemplate;
use Crypt,
    URL,
    DB;
use Request;
use CodePi\Base\Eloquent\EmailController;

class DataControllersMailer extends MyMailer {

    function dataControllerSendMail($data) {
        $ids = explode(",", $data['id']);
        foreach ($ids as $id) {
            $userData = EmailController::where('id', $id)->get();
            $objEmailcontroller = new EmailController;
            $userData = $objEmailcontroller->find($id);
            $data = array(
                'to_email' => $userData['to'],
                'from' => $userData['from'],
                'to_fname' => '',
                'from_name' => '',
                'subject' => $userData['subject'],
                'body' => $userData['message'],
                'id' => $userData['created_by'],
                'controller_id' => $userData['id'],
            );
            if (!empty($userData['attachment'])) {
                $data['attachment'] = json_decode($userData['attachment']);
            }
            $view = 'emails.emailcontent';
            $this->send($view, $data, $ec = true);
        }

        return 'success';
    }

}
