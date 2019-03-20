<?php

namespace CodePi\Events\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class AddItems extends BaseCommand{  
	
    public $id;
    public $items;
    /**
     * 
     * @param array $data
     */
    function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->items = PiLib::piIsset($data, 'items', []);
        $this->post = $data;
    }

}
