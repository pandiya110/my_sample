<?php

namespace CodePi\Settings\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class EmailDetailsMessage extends BaseCommand {

    public $id;

    function __construct($data) {
        parent::__construct();
        //$this->id = empty(PiLib::piIsset($data, 'id', '')) ? '' : PiLib::piDecrypt($data['id']);
        $this->id = PiLib::piIsset($data, 'id', '');
    }

}
