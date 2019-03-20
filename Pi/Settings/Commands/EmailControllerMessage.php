<?php

namespace CodePi\Settings\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class EmailControllerMessage extends BaseCommand {

    public $id;

    function __construct($data) {
        parent::__construct();
        //$this->id = empty(PiLib::piIsset($data, 'id', '')) ? '' : PiLib::piDecrypt($data['id']);
        $this->id = PiLib::piIsset($data, 'id', '');
    }

}
