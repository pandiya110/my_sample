<?php

namespace CodePi\Events\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;
class GetGlobal extends BaseCommand {

    public $events_id;

    /**
     * 
     * @param type $data
     */
    public function __construct($data) {
        parent::__construct(empty($data));
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
    }

}
