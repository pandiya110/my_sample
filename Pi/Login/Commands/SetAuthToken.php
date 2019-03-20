<?php

namespace CodePi\Login\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class SetAuthToken extends BaseCommand {
    /**
     *
     * @var string
     */
    public $token;
    /**
     *
     * @var int
     */
    public $users_id;
    /**
     *
     * @var date
     */
    public $expire_at;
    /**
     * 
     * @param type $data
     */
    function __construct($data) {

        $this->users_id = PiLib::piIsset($data, 'users_id', 0);
        $this->token = PiLib::piIsset($data, 'token', '');
        $this->expire_at = PiLib::piIsset($data, 'expire_at', gmdate('Y-m-d H:i:s'));
    }

}
