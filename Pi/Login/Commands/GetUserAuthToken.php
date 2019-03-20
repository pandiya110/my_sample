<?php

namespace CodePi\Login\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class GetUserAuthToken extends BaseCommand {

    /**
     *
     * @var string
     */
    public $token;

    function __construct($data) {


        $this->token = PiLib::piIsset($data, 'token', 0);
    }

}
