<?php

namespace CodePi\Users\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class CreateUser extends BaseCommand {

	public $firstname;
	public $id;
	public $lastname;
	public $email;	
	public $departments_id;	
	//public $password;
	public $status;
	public $last_modified_by;
	public $gt_last_modified;
	public $gt_date_added;
	public $last_modified;
	public $ip_address;
	public $permissions;
	//public $_token;
        public $profile_image_url;
        //public $sub_departments;
        //public $activate_exp_time;
        public $roles_id;
                
	function __construct($data) {
                        
            $permission = PiLib::piIsset($data, 'permissions', []);
            $data = $this->getUserPostData($data);
            $data['permissions'] = $permission;          
            parent::__construct(empty($data['id']));
	    $this->id =PiLib::piIsset($data,'id', ''); 
	    $this->firstname = PiLib::piIsset($data,'firstname', '');
	    $this->lastname = PiLib::piIsset($data,'lastname', '');	    
	    $this->email = PiLib::piIsset($data,'email', '');	     	  
	    $this->departments_id = PiLib::piIsset($data,'department', '');	    
	    //$this->password = PiLib::piIsset($data,'password', '');
	    $this->status = (isset($data['status']) && $data['status'] == true) ? '1' :'0';
            $this->profile_image_url = PiLib::piIsset($data,'image','');                       
            //$this->sub_departments = PiLib::piIsset($data,'sub_departments',[]);  
            $this->roles_id =PiLib::piIsset($data,'roles_id', 0); 
	    $this->permissions = PiLib::piIsset($data,'permissions',[]);
                     
	}
        
        function getUserPostData($data){
            $data = PiLib::piIsset($data,'user_details', []);
            return $data;
        }
}
