<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GroupedItemsList extends BaseCommand{  
        
        public $id;
        public $search;
        public $sort;
        public $order;
        public $perPage;
        public $page;        
        public $event_id;
        public $parent_item_id;
        public $is_no_record;
        public $item_sync_status;
        public $is_excluded;
        public $multi_sort;

        public function __construct($data) {
            parent::__construct(empty($data['id']));            
            $this->id = PiLib::piIsset($data,'id', ''); 
            $this->page = PiLib::piIsset($data, 'page', 1);
            $this->perPage = PiLib::piIsset($data, 'pageSize', '50');
            $this->search = PiLib::piSearchFilter($data, 'search', '');
            $this->order = PiLib::piIsset($data, 'column', '');
            $this->sort = PiLib::piIsset($data, 'sort', '');            
            $this->event_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;           
            $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);                                                                               
            $this->is_no_record = (isset($data['is_no_record']) && $data['is_no_record'] == true) ? '1' : '0';
            $this->is_excluded = (isset($data['is_excluded']) && $data['is_excluded'] == true) ? '1' : '0';
            $this->item_sync_status = (isset($data['item_sync_status']) && $data['item_sync_status'] == true) ? '1' : '0';            
            $this->multi_sort = PiLib::piIsset($data, 'multi_sort', []);
            
            
    }
    
        
}
