<?php

namespace CodePi\Users\DataSource;

use CodePi\Users\DataTranslators\UserTranslators;
use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\Users;
use Crypt;

class ForgotPassword {

	/**
	*Get user details using email.
	*@params $params
	*@return array $userDetails
	*/
    function getUserDetails($params) {
        $email = $params['email'];
        $userDetails = '';
        
        if (!empty($email)) {
             $userDetails = Users::where('email', $email)->first();
            //$userDetails = $this->model->where('email', $email)->first();
            //$userDetails = $this->model->where('email', $email)->where('password','!=','')->where('status','!=',0)->first(); 
        }
        return $userDetails;  
    } 
	
	   

}
