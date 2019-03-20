<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetItemsHeaders extends BaseCommand{  
        
    public $id;    
    public $linked_item_type;
    public $events_id;
    public $users_id;
    public $report_view;
    /**
     * 
     * @param type $data
     * 
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->id = PiLib::piIsset($data, 'id', 0);
        $this->users_id = PiLib::piIsset($data, 'users_id', 0);
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->linked_item_type = PiLib::piIsset($data, 'linked_item_type', 0);
        $this->report_view = PiLib::piIsset($data, 'report_view', false);
    }

}
