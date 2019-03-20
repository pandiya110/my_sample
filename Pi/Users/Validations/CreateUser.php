<?php

namespace CodePi\Users\Validations;

use CodePi\Base\Validations\DataValidator;
use CodePi\Base\Eloquent\Users;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;

class CreateUser extends DataValidator {
	private $walmartEmail = ['walmart.com', 'jet.com'];
        
	protected $rules = [ 
			"firstname" => "required|min:2|max:255",
			"lastname" => "required|min:2|max:255",
			"email"=>"required|email|isDynamicRule|max:255",
                        "departments_id"=>"required"
			
	];
	protected $messages=[
			'email.is_dynamic_rule' => 'Email address already exists.'
	];
	
	function doValidation($data){
                if (isset($data['id']) && $data['id'] == '') {
                    $data['id'] = 0;
                }    
                $isWalmartId = $this->isWalmartId($data);
                if($isWalmartId === true){
                    throw new DataValidationException('Sorry, we can\'t create users from admin by using this Email-id, kinldy login with SSO.', new MessageBag());
                }
		$objUsers= new Users;
		$responseOn = array();                
		$count = $objUsers->where('email',$data['email'])->where('id','!=',$data['id'])->count();                
		if ($count > 0) {
			return FALSE;
		}else{
			return TRUE;
		}

	}
        /**
         * 
         * @param type $data
         * @return boolean
         */
        function isWalmartId($data) {
            if (isset($data['email']) && !empty($data['email'])) {
                $domain = explode('@', str_lower($data['email']));
                if (isset($domain[1]) && !empty($domain[1])) {
                    if (in_array($domain[1], $this->walmartEmail)) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }

    //"email.isEmailExist" => ":attribute is already exist. Try with another Email." 
	//protected $extendValidation=array('properity'=>'isEmailExist','method'=>array('class','method'));
	
	/*function validateInformation(CommandContext $objCommandContext) {
		$response = TRUE;
		$PostVars = $objCommandContext->getParams();
		$objParent = new static ();
		$strTable = $objParent->table;
		$arrErrors = array();
		$arrValidator = array();
		$arrMessages = $objParent->validateMessages;
		$arrRules = $objParent->validateRules;
		$arrInput = array_intersect_key($PostVars, $arrRules);
		if (!empty($arrInput)) {
			if (isset($arrInput ['email'])) {
				Validator::extend('isEmailExist', function ($attribute, $value) use ($PostVars) {
					$isEmailExistOrNot = self::checkUnique($value, $PostVars ['id']);
					return $isEmailExistOrNot;
				});
			}
			$arrValidator = Validator::make($arrInput, $arrRules, $arrMessages);
			if ($arrValidator->fails()) {
				$arrErrors = array(
						'success' => FALSE,
						'type' => 'success',
						'message' => 'Erros! Please check Input Fields ',
						'result' => $arrValidator->messages()->toJson()
				);
				$objCommandContext->setError($arrErrors);
				$response = FALSE;
			}
		}
		return $response;
	}
	
	function checkUnique($strEmail, $intId) {
		
		
		$objUsers= new Users;
		$responseOn = array();
		$responseOn = $objUsers->where('email', '=', $strEmail)->orWhere ( function ($query) use ($intId) {
				
			if ($intId > 0) {
				$query->where( 'email', '!=', $strEmail );
			}
		
		} )->get ();
		
		 // print_r($responseOn);die;
		//$responseOn = $objUsers->where('email',$strEmail)->first();
		if ( isset($responseOn) && !empty($responseOn) ) {
			//echo 111;
			return FALSE;
		} else {
			// echo 222;
			return TRUE;
		}
		
	
		
	
	}*/
}
	

