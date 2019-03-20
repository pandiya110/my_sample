<?php

namespace CodePi\Events\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetEvents extends BaseCommand{  
        
    public $id;
    public $search;
    public $sort;
    public $order;
    public $perPage;
    public $page;
    public $start_date;
    public $end_date;
    public $is_draft;
    public $status_id;
    /**
     * 
     * @param type $data
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->id = (isset($data['id']) && !empty($data['id'])) ? PiLib::piDecrypt($data['id']) : 0;
        $this->page = PiLib::piIsset($data, 'page', 1);
        $this->perPage = PiLib::piIsset($data, 'pageSize', '500');
        $this->search = PiLib::piSearchFilter($data, 'searchVal', '');
        $this->order = PiLib::piIsset($data, 'column', '');
        $this->sort = PiLib::piIsset($data, 'sort', '');
        $this->start_date = (isset($data['event_start_date']) && $data['event_start_date'] != '') ? PiLib::piDate($data['event_start_date'], 'Y-m-d') : null;
        $this->end_date = (isset($data['event_end_date']) && $data['event_end_date'] != '') ? PiLib::piDate($data['event_end_date'], 'Y-m-d') : null;
        $this->is_draft = isset($data['is_draft']) ? $data['is_draft'] : '';
        $this->status_id = PiLib::piIsset($data, 'status_id', []);
        $this->post = $data;
    }

}
