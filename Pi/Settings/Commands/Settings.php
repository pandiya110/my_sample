<?php

namespace CodePi\Settings\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class Settings extends BaseCommand {

    public $stop_outgoing_emails;

    function __construct($data) {
        parent::__construct();
        $this->stop_outgoing_emails = PiLib::piIsset($data, 'stop_outgoing_emails', '');
    }

}
