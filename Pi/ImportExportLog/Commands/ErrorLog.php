<?php

namespace CodePi\ImportExportLog\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class ErrorLog extends BaseCommand {

    public $message;

    function __construct($data) {

        parent::__construct(TRUE);


        $this->message = PiLib::piIsset($data, 'message', ''); //Message

        $this->post = $data;
    }

}
