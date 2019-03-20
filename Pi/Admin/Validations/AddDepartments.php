<?php

namespace CodePi\Admin\Validations;

use CodePi\Base\Validations\DataValidator;
use CodePi\Base\Libraries\PiValidations;

class AddDepartments extends DataValidator { 

	public $rules = [ 
			'name' => 'required|min:2|max:250|isDynamicRule',			
                        //'prefix' => 'required|min:2|max:250|isDynamicRule',
	];  
        
        public function doValidation($data) {
        if (isset($data['id']) && $data['id'] == '') {
            $data['id'] = 0;
        }        
        $rules = [        
                'name' => 'unique:departments,id.neq',
                //'prefix' => 'unique:departments,id.neq',
        ];
        $messages = ['name.unique' => 'Department name already exists.', /*'prefix.unique' => 'Department Prefix already exists.'*/];
        $objPiValid = new PiValidations($data, $rules, $messages);
        return $objPiValid->validation();
               
    }
        
} 
 