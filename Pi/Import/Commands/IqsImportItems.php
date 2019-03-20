<?php

namespace CodePi\Import\Commands;

use Request;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class IqsImportItems extends BaseCommand {

    public $filename;
    public $events_id;
    public $items;
    public $search_key;
    public $userEditable;
    public $is_prize_req;
    public $users_id;
    public $new_ip_address;
    public $date_added;
    public $last_modified;

    /**
     * 
     * @param type $data
     */
    function __construct($data) {
        parent::__construct();
        $this->filename = PiLib::piIsset($data, 'filename', '');
        $this->event_id = isset($data['event_id']) ? ($data['event_id']) : 0;
        $this->search_key = PiLib::piIsset($data, 'search_key', '');
        $this->items = PiLib::piIsset($data, 'items', []);
        $this->userEditable = PiLib::piIsset($data, 'userEditable', []);
        $this->is_price_req = PiLib::piIsset($data, 'is_price_req', 1);
        $this->users_id = PiLib::piIsset($data, 'users_id', 1);
        $this->new_ip_address = PiLib::piIsset($data, 'new_ip_address', '127.1.1.0');
        $this->date_added = PiLib::piIsset($data, 'date_added', PiLib::piDate('Y-m-d H:i:s'));
        $this->last_modified = PiLib::piIsset($data, 'last_modified', PiLib::piDate('Y-m-d H:i:s'));
        $this->post = $data;
    }

}
