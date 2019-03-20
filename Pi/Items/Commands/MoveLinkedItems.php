<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;
class MoveLinkedItems extends BaseCommand{  
    /**
     *
     * @var array
     * @access public
     */
    public $id;

    /**
     *
     * @var int
     * @access public
     */
    public $event_id;

    /**
     *
     * @var array
     * @access public
     */
    public $item_id;
 /**
     *
     * @var string, edted value
     * @access public
     */
    public $parent_id;
    public $parent_item_id;
    /**
     * Constructor
     * @param array $data
     */
    function __construct($data) {

        parent::__construct($data);

        $this->id = PiLib::piIsset($data, 'id', '');
        $this->event_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->item_id = PiLib::piIsset($data, 'item_id', []);
        $this->parent_id = PiLib::piIsset($data, 'parent_id', 0);
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
    }

}
