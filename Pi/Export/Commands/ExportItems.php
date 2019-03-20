<?php
namespace CodePi\Export\Commands; 

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;


class ExportItems extends BaseCommand {
    /**
     *
     * @var int
     */
    public $event_id;
    /**
     *
     * @var string
     */
    public $type;
    /**
     *
     * @var string 
     */
    public $search;
    /**
     *
     * @var array
     */
    public $filters;
    /**
     *
     * @var bool
     */
    public $is_no_record;
    /**
     *
     * @var bool
     */
    public $item_sync_status;
    /**
     *
     * @var int
     */
    public $itemsListUserId;
    /**
     *
     * @var int
     */
    public $department_id;
    /**
     *
     * @var int 
     */
    public $users_id;
    /**
     *
     * @var enum
     */
    //public $linked_item_type;

    /**
     * 
     * @param array $data
     */
    public function __construct($data) {
        parent::__construct($data);

        $this->event_id = isset($data['event_id']) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->type = PiLib::piIsset($data, 'type', 1);
        $this->search = PiLib::piSearchFilter($data, 'search', '');
        $this->filters = PiLib::piIsset($data, 'filters', []);
        $this->is_no_record = (isset($data['is_no_record']) && $data['is_no_record'] == true) ? '1' : '0';
        $this->item_sync_status = (isset($data['item_sync_status']) && $data['item_sync_status'] == true) ? '1' : '0';
        $this->itemsListUserId = PiLib::piIsset($data, 'itemsListUserId', 0);
        $this->department_id = PiLib::piIsset($data, 'itemsListDepartmentId', 0);
        $this->users_id = PiLib::piIsset($data, 'users_id', 0);
        //$this->linked_item_type = PiLib::piIsset($data, 'linked_item_type', 0);
        $this->post = $data;
        
    }

}
