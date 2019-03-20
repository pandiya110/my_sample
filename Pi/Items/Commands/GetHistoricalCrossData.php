<?php

namespace CodePi\Items\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetHistoricalCrossData extends BaseCommand {
    
    /**
     *
     * @var int
     */
    public $item_nbr;
    public $sort;
    public $order;
    public $perPage;
    public $page;

    /**
     * Constructor 
     * @param array $data
     */
    function __construct($data) {

        parent::__construct(empty($data));
        $this->item_nbr = PiLib::piIsset($data, 'item_nbr', '');
        $this->page = PiLib::piIsset($data, 'page', 1);
        $this->perPage = PiLib::piIsset($data, 'pageSize', '50');            
        $this->order = PiLib::piIsset($data, 'column', '');
        $this->sort = PiLib::piIsset($data, 'sort', '');
        
    }

}
