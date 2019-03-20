<?php

namespace CodePi\Items\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class UnGroupItems extends BaseCommand {

    public $event_id;
    public $items_id;
    public $parent_item_id;
                
    function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->event_id = $this->event_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->items_id = PiLib::piIsset($data, 'items_id', []);    
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
    }

}
