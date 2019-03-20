<?php

namespace CodePi\SyncItems\Commands;

use Request,
    Hash,
    Crypt;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class CheckAvailability extends BaseCommand {

    public $event_id;
    public $item_id;
    public $users_id;

    /**
     * 
     * @param array $data
     */
    function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->event_id = PiLib::piIsset($data, 'event_id', 0); //(isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->item_id = PiLib::piIsset($data, 'item_id', []);
        $this->users_id = PiLib::piIsset($data, 'users_id', 1);       
    }

}
