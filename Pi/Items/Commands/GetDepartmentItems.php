<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetDepartmentItems extends BaseCommand{  
        
    public $id;
    /**
     * 
     * @param type $data
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->id = (isset($data['id']) && !empty($data['id'])) ? PiLib::piDecrypt($data['id']) : 0;
    }

}
