<?php

namespace CodePi\RestApiSync\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetItems extends BaseCommand {

    /**
     * Assign post value for to get the department list
     * @param object $data
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->post = $data;
    }

}
