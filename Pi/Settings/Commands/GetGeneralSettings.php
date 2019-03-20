<?php

namespace CodePi\Settings\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class GetGeneralSettings extends BaseCommand {

    function __construct($data) {
        parent::__construct();
    }

}
