<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Users\DataSource\ForgotPassword as ForgotPasswordDs;
use CodePi\Users\Mailer\UsersMailer;
use CodePi\Base\DataTransformers\DataResponse;

class ForgotPassword implements iCommands {

    private $dataSource;
    private $mailerSource;

    function __construct() {
        $this->dataSource = new ForgotPasswordDs ();
        $this->mailerSource = new UsersMailer ();
        $this->objDataResponse = new DataResponse();
    }

    function execute($command) {

        $status = false;
        $params = $command->dataToArray();
        unset($params ['_token']);
        $userDetails = $this->dataSource->getUserDetails($params);
        if (!empty($userDetails)) {
            $status = true;
            $this->mailerSource->forgotPasswordEmail($userDetails);
        }
        return $status;
    }

}
