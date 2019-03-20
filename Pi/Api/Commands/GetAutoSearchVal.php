<?php

namespace CodePi\Api\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetAutoSearchVal extends BaseCommand {
    
    /**
     *
     * @var int
     */
    public $search_val;
    /**
     *
     * @var string
     */
    public $search_key;
    /**
     *
     * @var int 
     */
    public $perPage;    
    /**
     *
     * @var int 
     */
    public $page;

    /**
     * Constructor 
     * @param array $data
     */
    function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->search_val = PiLib::piIsset($data, 'search_val', '');
        $this->search_key = PiLib::piIsset($data, 'search_key', 'searched_item_nbr');
        $this->page = PiLib::piIsset($data, 'page', '1');
        $this->perPage = PiLib::piIsset($data, 'pageSize', '50');
    }

}
