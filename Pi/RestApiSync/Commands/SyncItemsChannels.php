<?php

namespace CodePi\RestApiSync\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class SyncItems extends BaseCommand {

    public $action;
    

    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->action = PiLib::piIsset($data, 'action', 'update');        
        $this->post = $data;
    }

}
