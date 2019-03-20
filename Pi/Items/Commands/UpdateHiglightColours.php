<?php

namespace CodePi\Items\Commands;

use Request,
    Hash,
    Crypt;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class UpdateHiglightColours extends BaseCommand {
    /**
     *
     * @var int
     */
    public $events_id;
    /**
     *
     * @var array
     */
    public $color_code;
    /**
     *
     * @var int 
     */
    public $color_id;
    public $parent_item_id;
    
    /**
     * 
     * @param type $data
     */
    function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->color_code = PiLib::piIsset($data, 'color_code', []);
        $this->color_id = PiLib::piIsset($data, 'color_id', 0);
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
    }

}
