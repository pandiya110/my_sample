<?php

namespace CodePi\Events\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetUsersDropdown extends BaseCommand {
    public $users_id;
    public $search;
    public $perPage;
    public $page;

    /**
     * Assign post params to get the users list 
     * @param array $data
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->users_id = isset($data['users_id']) && !empty($data['users_id']) ? PiLib::piDecrypt($data['users_id']) : 0;
        $this->page = PiLib::piIsset($data, 'page', '1');
        $this->perPage = PiLib::piIsset($data, 'perPage', '50');
        $this->search = PiLib::piSearchFilter($data, 'search', '');
    }

}
