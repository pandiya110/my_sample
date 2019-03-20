<?php

namespace CodePi\Users\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class UserDetails extends BaseCommand { 
	public $id;
	function __construct($data) {			  
            parent::__construct(empty($data['id']));	    
	    $this->id =PiLib::piIsset($data,'id', 0); 
	} 
}
