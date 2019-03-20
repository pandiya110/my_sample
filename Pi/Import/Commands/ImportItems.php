<?php

namespace CodePi\Import\Commands;

use Request;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class ImportItems extends BaseCommand {

    public $filename;
    public $events_id;
    public $items;
    public $search_key;
    public $userEditable;
    public $is_prize_req;
    public $size = 10 * 1024 * 1024;
    public $extensions = array(
        'xls',
        'xlsx'
    );

    /**
     * 
     * @param type $data
     */
    function __construct($data) {
        parent::__construct();
        $this->extensions = PiLib::piIsset($data, 'extensions', $this->extensions);
        $this->size = PiLib::piIsset($data, 'size', $this->size);
        $this->filename = PiLib::piIsset($data, 'file', '');
        $this->event_id = isset($data['event_id']) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->search_key = PiLib::piIsset($data, 'search_key', '');
        $this->items = PiLib::piIsset($data, 'items', []);
        $this->userEditable = PiLib::piIsset($data, 'userEditable', []);
        $this->is_price_req = PiLib::piIsset($data, 'is_price_req', 1);
        $this->post = $data;
    }

}
