<?php

namespace CodePi\Users\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use CodePi\Base\Commands\BaseCommand;

class ForgotPasswordLinkValidate extends BaseCommand {  
	public $id;
	public $token;
	function __construct($id,$token) { 
 		$this->id = $id;
		$this->token = $token; 
	}
}
