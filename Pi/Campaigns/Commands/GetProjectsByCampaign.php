<?php

namespace CodePi\Campaigns\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetProjectsByCampaign extends BaseCommand {

    public $search;
    public $perPage;
    public $page;
    public $campaigns_id;

    public function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->campaigns_id = PiLib::piIsset($data, 'campaigns_id', 0);
        $this->page = PiLib::piIsset($data, 'page', '1');
        $this->perPage = PiLib::piIsset($data, 'pageSize', '25');
        $this->search = PiLib::piSearchFilter($data, 'search', '');
    }

}
