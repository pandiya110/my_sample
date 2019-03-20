<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;
class EditEventItem extends BaseCommand{  
    /**
     *
     * @var int
     * @access public
     */
    public $item_id;
    /**
     *
     * @var int
     * @access public
     */
    public $event_id;
    /**
     *
     * @var string, edited cell key name
     * @access public
     */
    public $item_key;
    /**
     *
     * @var string, edted value
     * @access public
     */
    public $value;
    
    public $parent_item_id;
    /**
     * Constructor
     * @param array $data
     */
    function __construct($data) {

        parent::__construct($data);
        $this->item_id = PiLib::piIsset($data, 'item_id', 0);
        $this->event_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0; //PiLib::piIsset($data,'event_id', 0); 
        $this->item_key = PiLib::piIsset($data, 'item_key', '');
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
        $this->value = isset($data['value']) ? $data['value'] : ''; //PiLib::piIsset($data, 'value', '');
        $this->post = $data;
    }

}
