<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class ItemsGroupList extends BaseCommand{  
        
        public $id;
        public $search;
        public $sort;
        public $order;
        public $perPage;
        public $page;        
        public $event_id;
        public $isexport;
        public $items_id;

        public function __construct($data) {
            parent::__construct(empty($data['id']));            
            $this->id = PiLib::piIsset($data,'id', ''); 
            $this->page = PiLib::piIsset($data, 'page', 1);
            $this->perPage = PiLib::piIsset($data, 'pageSize', '50');
            $this->search = PiLib::piSearchFilter($data, 'search', '');
            $this->order = PiLib::piIsset($data, 'column', '');
            $this->sort = PiLib::piIsset($data, 'sort', '');  
            $this->items_id = PiLib::piIsset($data, 'items_id', []);  
             $this->is_export = PiLib::piIsset($data, 'is_export', false);
            $this->event_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;           
    }
    
        
}
