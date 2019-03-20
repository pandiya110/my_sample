<?php

namespace CodePi\Users\Validations;

use CodePi\Base\Validations\DataValidator;
use CodePi\Base\Eloquent\Users;

class ForgotPassword extends DataValidator {

    protected $rules = [ 			
			"email"=>"required|email|isDynamicRule|max:255",
                        
	];
	protected $messages=[
			'email.is_dynamic_rule' => 'It looks like your account does not exist yet.'
	];

    function doValidation($data) {
        if (isset($data['id']) && $data['id'] == '') {
            $data['id'] = 0;
        }
        $objUsers = new Users;
        $responseOn = array();
        $count = $objUsers->where('email', $data['email'])->where('id', '!=', $data['id'])->count();
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

}
