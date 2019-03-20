<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;
class MoveItemsToLinkedItems extends BaseCommand{  
    /**
     *
     * @var array
     * @access public
     */
    public $items_id;

    /**
     *
     * @var int
     * @access public
     */
    public $events_id;

    
    /**
     * Constructor
     * @param array $data
     */
    function __construct($data) {

        parent::__construct($data);

        $this->items_id = PiLib::piIsset($data, 'id', '');
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
    }

}
