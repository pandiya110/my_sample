<?php

namespace CodePi\Import\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class BulkImportItemsCommand extends BaseCommand {

    public $filename;
    public $events_id;
    public $users_id;
    public $new_ip_address;
    public $date_added;
    public $last_modified;
    public $created_by;
    public $last_modified_by;

    function __construct($data) {
        parent::__construct();
        $this->filename = PiLib::piIsset($data, 'file', '');
        $this->events_id = PiLib::piIsset($data, 'event_id', 0);
        $this->users_id = PiLib::piIsset($data, 'users_id', 1);
        $this->new_ip_address = PiLib::piIsset($data, 'new_ip_address', '127.1.1.0');
        $this->date_added = PiLib::piIsset($data, 'date_added', PiLib::piDate('Y-m-d H:i:s'));
        $this->last_modified = PiLib::piIsset($data, 'last_modified', PiLib::piDate('Y-m-d H:i:s'));
        $this->created_by = PiLib::piIsset($data, 'created_by', 1);
        $this->last_modified_by = PiLib::piIsset($data, 'last_modified_by', 1);
        $this->post = $data;
    }

}
