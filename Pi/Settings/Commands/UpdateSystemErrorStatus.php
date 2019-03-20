<?php

namespace CodePi\Settings\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class UpdateSystemErrorStatus extends BaseCommand {

    public $id;
    public $status;

    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->id = PiLib::piIsset($data, 'id', 0);
        $this->status = PiLib::piIsset($data, 'status', 0);
    }

}
