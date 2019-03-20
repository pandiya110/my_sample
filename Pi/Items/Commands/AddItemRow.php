<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;
class AddItemRow extends BaseCommand{  
    /**
     *
     * @var int
     * @access public
     */
    public $events_id;
    /**
     *
     * @var type 
     */
    public $parent_item_id;
    /**
     * Constructor 
     * @param type $data
     */
    function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
    }

}
