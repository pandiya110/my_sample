<?php

namespace CodePi\Users\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class ResendActivationLink extends BaseCommand {

    public $id;

    public function __construct($data) {

        parent::__construct(TRUE);

        $this->id = PiLib::piIsset($data, 'id', '');
    }

}
