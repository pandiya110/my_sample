<?php

namespace CodePi\Login\Validations;

use CodePi\Base\Validations\DataValidator;

class ResetPasswordLink extends DataValidator {
	protected $rules = [ 
			"password" => "required" 
	]
	;
}
