<?php

namespace CodePi\Users\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class ResetPasswordLink extends BaseCommand {

	public $password;
	public $newPassword;
	public $id;
	public $token;
	
	function __construct($data) {
		$this->id = $data['id'];
		$this->token = $data['token'];  
	    $this->password =PiLib::piIsset($data,'password', '');
	    $this->newPassword =PiLib::piIsset($data,'newPassword', '');
	}
}
