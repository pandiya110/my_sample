<?php

namespace CodePi\ItemsCardView\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class ExportCardViewPdf extends BaseCommand {

    public $search;
    public $sort;
    public $order;
    public $perPage;
    public $page;
    public $event_id;
    public $columns_array;
    public $parent_item_id;
    public $column;
    public $items_id;
    public $itemsListUserId;
    public $department_id;
    /**
     * 
     * @param array $data
     */
    public function __construct($data) {
        parent::__construct(empty($data['id']));

        $this->page = PiLib::piIsset($data, 'page', 1);
        $this->perPage = PiLib::piIsset($data, 'pageSize', '');
        $this->search = PiLib::piSearchFilter($data, 'search', '');
        $this->order = PiLib::piIsset($data, 'column', '');
        $this->sort = PiLib::piIsset($data, 'sort', '');
        $this->event_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->columns_array = PiLib::piIsset($data, 'columns_array', []);
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
        $this->column = PiLib::piIsset($data, 'column', []);
        $this->items_id = PiLib::piIsset($data, 'items_id', []);
        $this->itemsListUserId = PiLib::piIsset($data, 'itemsListUserId', 0);
        $this->department_id = PiLib::piIsset($data, 'itemsListDepartmentId', 0);
    }

}
