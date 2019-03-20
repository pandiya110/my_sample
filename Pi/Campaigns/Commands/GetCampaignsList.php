<?php

namespace CodePi\Campaigns\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetCampaignsList extends BaseCommand{  
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
     * Assign post params to get the campaigns list 
     * @param array $data
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->id = PiLib::piIsset($data, 'id', 0);
        $this->page = PiLib::piIsset($data, 'page', '1');
        $this->perPage = PiLib::piIsset($data, 'pageSize', '25');
        $this->search = PiLib::piSearchFilter($data, 'search', '');
        $this->sort = PiLib::piIsset($data, 'sort', '');
        $this->status = (isset($data['active']) && $data['active'] == false) ? '0' : '1';
        $this->post = $data;
    }

}
