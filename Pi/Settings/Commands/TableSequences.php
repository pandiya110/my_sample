<?php

namespace CodePi\Settings\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class TableSequences extends BaseCommand {

    public $page;
    public $perPage;
    public $sortBy;
    public $sort;
    public $order;

    function __construct($data) {
        // echo "<pre>";print_r($data);exit;
        parent::__construct();
        $this->page = isset($data['pageNumber']) ? $data['pageNumber'] : 1;
        $this->perPage = isset($data['pageSize']) ? $data['pageSize'] : 25;
        $this->sortBy = isset($data['sortBy']) ? $data['sortBy'] : '';
        $this->sort = PiLib::piIsset($data, 'sort', 'DESC');
        $this->order = PiLib::piIsset($data, 'order', 'asc');
    }

}
