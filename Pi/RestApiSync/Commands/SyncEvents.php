<?php

namespace CodePi\RestApiSync\Commands;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;


class SyncEvents extends BaseCommand {

    public $table_name;

    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->table_name = PiLib::piIsset($data, 'table_name', 'events');
        $this->post = $data;
    }

}
