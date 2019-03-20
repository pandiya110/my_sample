<?php

namespace CodePi\Settings\Commands;

#use Symfony\Component\HttpFoundation\Session\Session;

use CodePi\Base\Commands\BaseCommand;

class EmailControllerDetails extends BaseCommand {

    public $id;

    function __construct($data) {
        $this->id = isset($data) ? $data : '';
    }

}
