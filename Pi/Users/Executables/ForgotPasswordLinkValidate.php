<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Login\DataSource\ForgotPasswordLinkValidate as CheckInListDs; 
class ForgotPasswordLinkValidate implements iCommands {
	private $dataSource;
	function __construct() {
		$this->dataSource = new CheckInListDs ();
	}
	function execute($command) {
		$data = $command->dataToArray ();  
		$result = $this->dataSource->CheckInListData ( $data );
		return $result;
	}
}
