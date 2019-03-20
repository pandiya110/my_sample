<?php

namespace CodePi\Templates\Validations;

use CodePi\Base\Validations\DataValidator;
use CodePi\Base\Libraries\PiValidations;
use CodePi\Base\Eloquent\UsersTemplates;


class SaveUsersTemplates extends DataValidator { 

	public $rules = [ 
			'name' => 'required|min:2|max:255|isDynamicRule',			
	];  
        
        protected $messages=[
			'name.is_dynamic_rule' => 'Template Name already exists. Please try again.'
	];
        
        /**
         * Template name validations, name will be unique
         * @param array $data
         * @return boolean
         */
        public function doValidation(array $data) {  
                        
            if (isset($data['id']) && $data['id'] == '') {
                $data['id'] = 0;
            }
            $objTemp = new UsersTemplates();
            $intCount = $objTemp->where('name', trim($data['name']))
                                ->where('users_id', $data['users_id'])
                                ->where('id', '!=', $data['id'])
                                ->count();
            if($intCount > 0){
                return false;
            }else{
                return true;
            }                  
        }
        
} 
 