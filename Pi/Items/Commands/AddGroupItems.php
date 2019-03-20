<?php

namespace CodePi\Items\Commands;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class AddGroupItems extends BaseCommand {

    public $id;
    public $event_id;
    public $items_id;
    public $items;
    public $name;
    public $items_groups_id;

    public function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->items_id = PiLib::piIsset($data, 'items_id', '');
        $this->name = PiLib::piIsset($data, 'name', '');
        $this->items = PiLib::piIsset($data, 'items', []);
        $this->items_groups_id = PiLib::piIsset($data, 'items_groups_id', 0);
    }

}
