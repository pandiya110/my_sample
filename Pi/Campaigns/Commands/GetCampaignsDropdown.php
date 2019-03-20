<?php

namespace CodePi\Campaigns\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetCampaignsDropdown extends BaseCommand {

    public $search;
    public $perPage;
    public $page;

    /**
     * Assign post params to get the campaigns list 
     * @param array $data
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->page = PiLib::piIsset($data, 'page', '1');
        $this->perPage = PiLib::piIsset($data, 'pageSize', '25');
        $this->search = PiLib::piSearchFilter($data, 'search', '');
    }

}
