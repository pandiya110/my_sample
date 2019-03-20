<?php

namespace CodePi\Templates\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class AssignDefaultTemplate extends BaseCommand {

    public $id;
    public $users_id;
    public $is_default;

    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->id = PiLib::piIsset($data, 'id',0);
        $this->users_id = PiLib::piIsset($data, 'users_id', 0);
        $this->is_default = PiLib::piIsset($data, 'is_default', false);
    }

}
