<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;
class GetItemsPriceZones extends BaseCommand{  
    /**
     * @access public
     * @var int $id
     */
    public $id;
    /**
     * @access public
     * @var string $action 1->Append; 2->Replace;
     */        
    public $events_id;
   
    function __construct($data) {

        parent::__construct($data);
        $this->id = PiLib::piIsset($data, 'id', 0);
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;       
    }

}
