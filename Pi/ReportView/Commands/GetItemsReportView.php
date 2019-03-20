<?php

namespace CodePi\ReportView\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetItemsReportView extends BaseCommand {

    public $search;
    public $sort;
    public $order;
    public $perPage;
    public $page;
    public $is_no_record;
    public $item_sync_status;
    public $is_export;
    public $itemsListUserId;
    public $department_id;
    public $users_id;
    public $multi_sort;
    public $parent_item_id;
    public $event_id;
    public $item_type;

    public function __construct($data) {
        parent::__construct(empty($data['id']));

        $this->page = PiLib::piIsset($data, 'page', 1);
        $this->perPage = PiLib::piIsset($data, 'pageSize', '100');
        $this->search = PiLib::piSearchFilter($data, 'search', '');
        $this->order = PiLib::piIsset($data, 'column', '');
        $this->sort = PiLib::piIsset($data, 'sort', '');
        $this->is_no_record = (isset($data['is_no_record']) && $data['is_no_record'] == true) ? '1' : '0';
        $this->item_sync_status = (isset($data['item_sync_status']) && $data['item_sync_status'] == true) ? '1' : '0';
        $this->is_export = PiLib::piIsset($data, 'is_export', false);
        $this->itemsListUserId = PiLib::piIsset($data, 'itemsListUserId', 0);
        $this->department_id = PiLib::piIsset($data, 'itemsListDepartmentId', 0);
        $this->users_id = PiLib::piIsset($data, 'user_id', 0);
        $this->multi_sort = PiLib::piIsset($data, 'multi_sort', []);
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
        $this->event_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;           
        $this->item_type = PiLib::piIsset($data, 'item_type', '0');
        $this->post = $data;
        
    }

}
