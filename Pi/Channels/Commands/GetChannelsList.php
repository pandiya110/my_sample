<?php

namespace CodePi\Channels\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetChannelsList extends BaseCommand{  
    /**
     *
     * @var int 
     */
    public $id;
    /**
     *
     * @var string 
     */
    public $search;
    /**
     *
     * @var string
     */
    public $sort;
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
     *
     * @var boolean 
     */
    public $status;
    /**
     * 
     * @param type $data['id']       = (int)  Channels id, primary key of table
     *                  ['page']     = (int)  No of page
     *                  ['pageSize'] = (int)  maximum no of records to display in list
     *                  ['search']   = (string) Search key, to get the result by search values
     *                  ['sort']     = (string) Sorting by Asc or Desc
     *                  ['active']   = (boolean)Status of Channels Active or Inactive
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->id = PiLib::piIsset($data, 'id', 0);
        $this->page = PiLib::piIsset($data, 'page', '');
        $this->perPage = PiLib::piIsset($data, 'pageSize', '25');
        $this->search = PiLib::piSearchFilter($data, 'search', '');
        $this->sort = PiLib::piIsset($data, 'sort', '');
        $this->status = (isset($data['active']) && $data['active'] == false) ? '0' : '1';
        $this->post = $data;
    }

}
