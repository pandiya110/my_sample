<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class CopyItems extends BaseCommand {
    /**
     *
     * @var int 
     */
    public $from_events_id;
    /**
     *
     * @var int
     */
    public $to_events_id;
    /**
     *
     * @var array
     */
    public $items_id;
    public $parent_item_id;
    /**
     * Constructor
     * @param array $data
     */
    function __construct($data) {

        parent::__construct(empty($data));        
        $this->from_events_id = (isset($data['from_events_id']) && !empty($data['from_events_id'])) ? PiLib::piDecrypt($data['from_events_id']) : 0;
        $this->to_events_id = (isset($data['to_events_id']) && !empty($data['to_events_id'])) ? PiLib::piDecrypt($data['to_events_id']) : 0;           
        $this->items_id = PiLib::piIsset($data, 'items_id', []);
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
    }

}
