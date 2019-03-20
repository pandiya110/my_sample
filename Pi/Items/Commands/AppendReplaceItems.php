<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;
class AppendReplaceItems extends BaseCommand{  
    /**
     * @access public
     * @var int $id
     */
    public $id;
    /**
     * @access public
     * @var string $action 1->Append; 2->Replace;
     */
    public $action;
    /**
     * @access public
     * @var string $item_key, edited cell key name
     */
    public $item_key;
    /**
     * @access public
     * @var int
     */
    public $events_id;
    /**
     * @access public
     * @var string
     */
    public $item_value;
    public $parent_item_id;
    /**
     * Constructor
     * 
     * @param array $data
     */
    function __construct($data) {

        parent::__construct($data);
        $this->id = PiLib::piIsset($data, 'id', 0);
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->action = PiLib::piIsset($data, 'action', '');
        $this->item_key = PiLib::piIsset($data, 'item_key', '');
        $this->item_value = PiLib::piIsset($data, 'item_value', '');
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
    }

}
