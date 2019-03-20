<?php

namespace CodePi\Users\Validations;

use CodePi\Base\Validations\DataValidator;

class UserDetails extends DataValidator {
	protected $rules = [ 
			"id" => "required|integer" 
	];
}
