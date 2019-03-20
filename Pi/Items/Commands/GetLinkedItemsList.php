<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetLinkedItemsList extends BaseCommand{  
        
    public $id;
    public $search;
    public $sort;
    public $order;
    public $perPage;
    public $page;
    //public $user_id;
    public $event_id;
    public $itemsListUserId;
    public $department_id;
    public $parent_id;
    public $is_export;
    public $export_option;
    
    /**
     * 
     * @param array $data
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->id = PiLib::piIsset($data, 'id', 0);
        $this->page = PiLib::piIsset($data, 'page', 1);
        $this->perPage = PiLib::piIsset($data, 'pageSize', '1000');
        $this->search = PiLib::piSearchFilter($data, 'search', '');
        $this->order = PiLib::piIsset($data, 'column', '');
        $this->sort = PiLib::piIsset($data, 'sort', '');
        //$this->user_id = PiLib::piIsset($data, 'user_id', 0);
        $this->event_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->itemsListUserId = PiLib::piIsset($data, 'itemsListUserId', 0);
        $this->department_id = PiLib::piIsset($data, 'itemsListDepartmentId', 0);
        $this->parent_id = PiLib::piIsset($data, 'parent_id', 0);
        $this->is_export = PiLib::piIsset($data, 'is_export', false);
        $this->export_option = PiLib::piIsset($data, 'export_option', '1');
        $this->post = $data;
        
    }

}
