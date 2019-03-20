<?php

namespace CodePi\Settings\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class UpdateSequences extends BaseCommand {

    public $schema;
    public $tableName;

    function __construct($data) {
        parent::__construct();
        $this->schema = PiLib::piIsset($data, 'schema', '');
        $this->tableName = PiLib::piIsset($data, 'tablename', '');
    }

}
