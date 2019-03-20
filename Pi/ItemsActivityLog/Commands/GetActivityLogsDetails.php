<?php

namespace CodePi\ItemsActivityLog\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,
    Hash,
    Crypt;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class GetActivityLogsDetails extends BaseCommand {
    /**
     *
     * @var string
     */
    public $tracking_id;
    /**
     *
     * @var string 
     */
    public $action;
    /**
     *
     * @var string 
     */
    public $type;
    
    public $events_id;
    /**
     * 
     * @param array $data
     */
    function __construct($data) {

        parent::__construct($data);
        $this->tracking_id = PiLib::piIsset($data, 'tracking_id', 0);
        $this->action = PiLib::piIsset($data, 'action', '');
        $this->type = PiLib::piIsset($data, 'type', '0');
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
    }

}
