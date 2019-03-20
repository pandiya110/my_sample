<?php

namespace CodePi\Events\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetEventsDropdown extends BaseCommand {
    public $events_id;
    public $search;
    public $perPage;
    public $page;

    /**
     * Assign post params to get the campaigns list 
     * @param array $data
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->events_id = isset($data['event_id']) && !empty($data['event_id']) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->page = PiLib::piIsset($data, 'page', '');
        $this->perPage = PiLib::piIsset($data, 'pageSize', '');
        $this->search = PiLib::piSearchFilter($data, 'search', '');
    }

}
