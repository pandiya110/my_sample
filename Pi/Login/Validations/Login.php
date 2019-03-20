<?php

namespace CodePi\Login\Validations;

use CodePi\Base\Validations\DataValidator;
use CodePi\Base\Eloquent\Users;
use CodePi\Base\Libraries\PiValidations;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
class Login extends DataValidator {

    public $rules = [
        'username' => 'required|email|isDynamicRule',
        //'password' => 'required'
    ];
    protected $messages = [
        'username.is_dynamic_rule' => 'It looks like your account does not exist yet',        
    ];

    function doValidation($data) {
        if (isset($data['id']) && $data['id'] == '') {
            $data['id'] = 0;
        }

        $objUsers = new Users;
        $responseOn = array();
        $count = $objUsers->where('email', trim($data['username']))->count();

        if ($count > 0) {
            $rule = $this->isActivatedUsers($data);
            if ($rule['activated'] == false) {
                throw new DataValidationException('Your account has not yet been activated.', new MessageBag());
            } else if ($rule['status'] == false) {
                throw new DataValidationException('Account is inactive. Please email mktechhelp@walmart.com to request an activation email.', new MessageBag());
            }
            return true;
        } else {
            return false;
        }
    }
    /**
     * Check logged in users is Active and Activated status
     * 
     * @param int $data
     * @return array
     */
    function isActivatedUsers($data) {
        if (isset($data['id']) && $data['id'] == '') {
            $data['id'] = 0;
        }
        $objUsers = new Users;
        $userData = $objUsers->where('email', trim($data['username']))->first();
        return ['activated' => ($userData->is_register == '1') ? true : false, 'status' => ($userData->status == '1') ? true : false];
    }

}
