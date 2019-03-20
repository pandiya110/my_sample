<?php

namespace CodePi\Users\Validations;

use CodePi\Base\Validations\DataValidator;
use CodePi\Base\Libraries\PasswordValidationRules;
use CodePi\Base\Eloquent\Users;
use Hash;
class ResetPassword extends DataValidator {

    protected $rules = [
        "id" => "required",
        //"token" => "required",
        "password" => "required|same:newPassword|isDynamicRule",
       
        "newPassword" => "required"
    ];
    
    
        protected $messages=[
			'password.is_dynamic_rule' => ''
	];
        function doValidation($data){
            $objValid=  new PasswordValidationRules();
            $objValid->setPassword($data['password']);
            $objValid->setisPasswordLength(true);
            $objValid->setisCapitalCase(true);
            $objValid->setisLowerCase(true);
            $objValid->setisNumberCase(true);
            $value= $objValid->validatePassword($data);
            
            if($value == 1) {
                if(!empty($data['newPassword'])) {
                $res = Users::find($data['id']);
                if(!empty($res)){
                    if(Hash::check($data['password'], $res->password)) {
                        $this->messages['password.is_dynamic_rule'] = 'Password Cannot be same as Current Password';
                        return FALSE;
                    }else{
                        return TRUE;
                    }
                }else{
                    $this->messages['password.is_dynamic_rule'] = 'Invalid.Please Try Again';
                    return FALSE;
                }        

               } else {
                   return TRUE;
               }
            }else{   
                $this->messages['password.is_dynamic_rule'] = $value;             
                return FALSE;
            }
           	
               
			
	}

}
