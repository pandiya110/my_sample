<?php

namespace CodePi\Users\Commands;


use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

/**
 * @ignore It will reveals the table fields and fectech the input data to database fields
 */
class UsersData extends BaseCommand {

    public $id;
    public $page;
    public $perPage;
    public $search;
    public $sort;
    
    public function __construct($data) {
       
        parent::__construct(TRUE); 
        
        $this->id = PiLib::piIsset($data, 'id', '');
        $this->page = PiLib::piIsset($data, 'page', 1);
        $this->perPage = PiLib::piIsset($data, 'pageSize', '25');
        $this->search = PiLib::piSearchFilter($data, 'search', '');
        $this->sort = PiLib::piIsset($data, 'sort', '');
        $this->post = $data;
        
    }

}
