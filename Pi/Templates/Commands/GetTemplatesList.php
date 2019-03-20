<?php

namespace CodePi\Templates\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetTemplatesList extends BaseCommand {

    public $users_id;

    function __construct($data) {
        parent::__construct(empty($data['id']));

        $this->users_id = PiLib::piIsset($data, 'users_id', 0);
    }

}
