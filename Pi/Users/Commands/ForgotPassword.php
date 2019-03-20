<?php

namespace CodePi\Users\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class ForgotPassword extends BaseCommand {

    public $email;
    public $_token;

    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->email = PiLib::piIsset($data, 'email', '');
        $this->_token = csrf_token();
    }

}
