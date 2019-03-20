<?php

namespace CodePi\Events\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;
class AddEvents extends BaseCommand{  
	
    public $id;
    public $name;
    public $start_date;
    public $end_date;
    public $statuses_id;
    public $is_draft;
    public $campaigns_id;
    public $access_type;
    public $campaigns_projects_id;
    /**
     * 
     * @param type $data
     */
    function __construct($data) {

        parent::__construct(empty($data['id']));
        $defaultStatus = 1;
        $this->id = (isset($data['id']) && !empty($data['id'])) ? PiLib::piDecrypt($data['id']) : 0;
        $this->name = (isset($data['name']) && !empty($data['name'])) ? PiLib::filterString($data['name']) : ''; //PiLib::piIsset($data, 'name', '');
        $this->start_date = (isset($data['start_date']) && $data['start_date'] != '') ? PiLib::piDate($data['start_date'], 'Y-m-d') : null;
        $this->end_date = (isset($data['end_date']) && $data['end_date'] != '') ? PiLib::piDate($data['end_date'], 'Y-m-d') : null;
        $this->is_draft = PiLib::piIsset($data, 'is_draft', '0');
        $this->campaigns_id = PiLib::piIsset($data, 'campaigns_id', 0);
        $this->campaigns_projects_id = PiLib::piIsset($data, 'campaigns_projects_id', 0);
        if ($data['is_draft'] == '1') {
            $defaultStatus = 4;
        }
        $this->statuses_id = PiLib::piIsset($data, 'status_id', $defaultStatus);
        $this->users_id = (isset($data['users_id']) && !empty($data['users_id'])) ? PiLib::filterString($data['users_id']) : ''; 
        $this->access_type = isset($data['access_type']) ? (string)$data['access_type'] : '1';

    }

}
