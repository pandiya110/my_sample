<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,
    Hash,
    Crypt;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class DuplicateItems extends BaseCommand {
    /**
     *
     * @var int 
     */
    public $events_id;
    /**
     *
     * @var array 
     */
    public $item_id;
    /**
     *
     * @var type 
     */
    public $single_item;
    public $parent_item_id;
    /**
     * 
     * @param array $data
     */
    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->events_id = isset($data['event_id']) && !empty($data['event_id']) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->item_id = PiLib::piIsset($data, 'item_id', []);
        $this->single_item = PiLib::piIsset($data, 'single_item', false);
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
    }

}
