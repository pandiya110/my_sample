<?php

namespace CodePi\Settings\DataSource;

use CodePi\Settings\Mailer\DataControllersMailer;
use CodePi\Users\DataTranslators\UserTranslators;
use CodePi\Base\Eloquent\EmailController;
use DB;

class EmailControllerDataSource {

    private $mailerSource;
    private $objEmailController;

    /**
     * get all email Controller  details. 
     * @param $usersListCommand
     * @return array $users
     */
    function emailControllerData($data) {


        $data['limit'] = '';
        if (isset($data['page'])) {
            $data['limit'] = ($data['page'] - 1) * $data['perPage'];
        }
        // print_r($data);die;
        return $emailControllers = EmailController::skip($data['limit'])->take($data['perPage'])->orderBy($data['order'], $data['sort'])->get();
        //return $emailControllers = EmailController::skip($data['limit'])->take($data['perPage'])->orderBy('id', 'desc')->get(); 
    }

    /**
     * get all email Controller  Count.
     * @param 
     * @return array $users
     */
    function emailControllerCount() {

        return $emailControllersCount = EmailController::count();
    }

    /**
     * get all email Controller  details. 
     * @param $usersListCommand
     * @return array $users
     */
    function getEmailControllerdata($data) {

        return $emailControllerInfo = $this->model->find($data);
    }

    /**
     * Mail send to selected data controllers. 
     * @param array $data
     * @return array $users
     */
    function emailControllerSendMail($command) {
        $data = $command->dataToArray();
        $objDataControllerMailer = new DataControllersMailer;
        return $dataControllerMail = $objDataControllerMailer->dataControllerSendMail($data);
    }

    /*
     * find the email message of specified id.
     * @param int $id
     * @return $emailControllerMessage
     */

    function getEmailControllerMessage($command) {
        $params = $command->dataToArray();
        $emailControllerMessage = EmailController::where('id', $params['id'])->get();
        return $emailControllerMessage;
    }
    /**
     * 
     * @param type $command
     * @return type
     */
    function SendMailEmailControllersData($command) {
        $data = $command->dataToArray();
        $ids = explode(",", $data['id']);
        $emailControllerData = EmailController::whereIn('id', $ids)->get();
        return $emailControllerData;
    }

}
