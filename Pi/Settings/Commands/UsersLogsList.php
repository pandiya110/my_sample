<?php

namespace CodePi\Settings\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class UsersLogsList extends BaseCommand {

    public $page;
    public $perPage;
    public $user_id;
    public $sort;
    public $order;

    function __construct($data) {
        parent::__construct();
        $this->page = isset($data['pageNumber']) ? intval($data['pageNumber']) : 1;
        $this->perPage = isset($data['perPage']) ? intval($data['perPage']) : 50;
        $this->user_id = \Auth::user()->id;
        $this->sort = PiLib::piIsset($data, 'sort', 'DESC');
        $this->order = PiLib::piIsset($data, 'orderBy', 'id');
    }

}
