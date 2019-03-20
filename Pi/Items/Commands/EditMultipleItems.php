<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class EditMultipleItems extends BaseCommand {
    
    /**
     *
     * @var int
     */
    public $events_id;
    /**
     *
     * @var array 
     */
    public $list;
    /**
     *
     * @var string 
     */
    public $value;
    /**
     *
     * @var string
     */
    public $item_key;
    public $parent_item_id;

    /**
     * Constructor
     * @param array $data
     */
    function __construct($data) {

        parent::__construct($data);
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0; //PiLib::piIsset($data,'event_id', 0); 
        $this->list = PiLib::piIsset($data, 'list', []);
        $this->value = PiLib::piIsset($data, 'value', '');
        $this->item_key = PiLib::piIsset($data, 'item_key', '');
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
    }

}
