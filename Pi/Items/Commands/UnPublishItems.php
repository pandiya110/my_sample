<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;
class UnPublishItems extends BaseCommand{  
    /**
     *
     * @var int
     * @access public
     */
    public $events_id;
    /**
     *
     * @var array
     * @access public
     */
    public $item_id;
    public $parent_item_id;

    /**
     * 
     * @param type $data
     */
    function __construct($data) {

        parent::__construct($data);
        $this->events_id = $this->event_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->item_id = PiLib::piIsset($data, 'item_id', []);
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
    }

}
