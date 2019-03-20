<?php

namespace CodePi\Api\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class SendNotificationToApi extends BaseCommand {

    public $access_token;
    public $refresh_token;
    
    function __construct($data) {
        $this->access_token = PiLib::piIsset($data, 'access_token', '');
        $this->refresh_token = PiLib::piIsset($data, 'refresh_token', '');
    }
}
