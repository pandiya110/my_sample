<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Users\DataSource\UsersDataSource as UsersDs; 

class UpdateProfilePic implements iCommands {

    private $dataSource;        
        /**
        * @ignore It will create an object of Users
        */
        function __construct(UsersDs $objUsersDs) {
		$this->dataSource = $objUsersDs;  
                
	}
        
    /**
     * 
     * @param type $command
     * @return type $response
     */    
    function execute($command) {
        $data = $command->dataToArray();        
        $response = $this->dataSource->uploadImage($data);
        return $response;
       
    }

    

}
