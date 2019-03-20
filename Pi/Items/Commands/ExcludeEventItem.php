<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;
class ExcludeEventItem extends BaseCommand{  
    /**
     *
     * @var array
     * @access public
     */
    public $id;
    /**
     *
     * @var boolean
     * @access public
     */
    public $action;
    public $parent_item_id;
    
    /**
     * Constructor
     * 
     * @param array $data
     */
    function __construct($data) {

        parent::__construct($data);

        $this->id = PiLib::piIsset($data, 'id', '');
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0; //PiLib::piIsset($data,'event_id', 0); 
        $this->action = (isset($data['action']) && $data['action'] == true) ? '1' : '0';
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
    }

}
