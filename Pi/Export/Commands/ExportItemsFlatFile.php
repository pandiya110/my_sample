<?php

namespace CodePi\Export\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class ExportItemsFlatFile extends BaseCommand {

    public $users_id;

    public function __construct($data) {
        parent::__construct($data);
        $this->users_id = PiLib::piIsset($data, 'users_id', 1);
        $this->post = $data;
    }

}
