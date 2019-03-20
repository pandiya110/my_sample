<?php

namespace CodePi\Templates\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class CopyTemplates extends BaseCommand {

    public $id;
    public $users_id;

    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->id = PiLib::piIsset($data, 'id', 0);
        $this->users_id = PiLib::piIsset($data, 'users_id', 0);
    }

}
