<?php

namespace CodePi\Aprimo\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class GetAprimoActivities extends BaseCommand {

    public $data;

    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->data = $data;
    }

}
