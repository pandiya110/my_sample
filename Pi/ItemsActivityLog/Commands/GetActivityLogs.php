<?php

namespace CodePi\ItemsActivityLog\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,
    Hash,
    Crypt;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class GetActivityLogs extends BaseCommand {
    /**
     *
     * @var int
     */
    public $events_id;        
    /**
     *
     * @var int
     */
    public $perPage;
    /**
     *
     * @var int
     */
    public $page;
    /**
     * 
     * @param array $data
     */
    function __construct($data) {

        parent::__construct($data);
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->page = PiLib::piIsset($data, 'page', 1);
        $this->perPage = PiLib::piIsset($data, 'pageSize', '500');
        
    }

}
