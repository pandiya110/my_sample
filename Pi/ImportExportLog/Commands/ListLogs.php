<?php

namespace CodePi\ImportExportLog\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

/**
 * @ignore It will reveals the table fields and fectech the input data to database fields
 */
class ListLogs extends BaseCommand {

    public $sort;
    public $order;
    public function __construct($data) {
    	parent::__construct();
        $this->sort = PiLib::piIsset($data, 'sort', 'asc');
        $this->order = PiLib::piIsset($data, 'order', 'action');
    }

}
