<?php

namespace CodePi\Events\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetEventDetails extends BaseCommand{  
        
    public $id;
    public $user_id;
    /**
     * 
     * @param type $data
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->id = (isset($data['id']) && !empty($data['id'])) ? PiLib::piDecrypt($data['id']) : 0;
        $this->user_id = PiLib::piIsset($data, 'user_id', 0);
    }

}
