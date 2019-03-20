<?php

namespace CodePi\Events\Validations;

use CodePi\Base\Validations\DataValidator;
use CodePi\Base\Libraries\PiValidations;
use CodePi\Base\Eloquent\Events;


class AddEvents extends DataValidator { 

	public $rules = [ 
			'name' => 'required|min:2|max:255|isDynamicRule',			
	];  
        
        protected $messages=[
			'name.is_dynamic_rule' => 'Event already exists.'
	];
        
        /**
         * Unique Validation for Events Name
         * @param int $data['id']
         * @param string $data['start_date']
         * @param string $data['end_date']
         * @param string $data['is_draft']
         * @return boolean true or false
         * @access public
         */
        public function doValidation($data) {  
                        
            if (isset($data['id']) && $data['id'] == '') {
                $data['id'] = 0;
            }
            
            $objEvents = new Events();            
            $intCount = $objEvents->dbTable('e')                        
                        ->where('name', trim($data['name']))
                        ->where('start_date', $data['start_date'])
                        ->where('end_date', $data['end_date'])
                        ->where('e.id','!=',$data['id'])
                        ->where(function ($query) use ($data) {                        
                        if ($data['is_draft'] == '0') {
                            $query->where('e.is_draft', '0');
                        }else if($data['is_draft'] == '1'){
                            $query->where('e.is_draft', '1')->where('e.created_by', $data['last_modified_by']);                                    
                        }                                               
                        })->count();                 
             
            if($intCount > 0){
                return false;
            }else{
                return true;
            }                  
    }
        
} 
 