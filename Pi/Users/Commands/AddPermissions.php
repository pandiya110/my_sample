<?php

namespace CodePi\Users\Commands;


use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

/**
 * @ignore It will reveals the table fields and fectech the input data to database fields
 */
class AddPermissions extends BaseCommand {

    public $profile_id;
    public $permissions;
    
    public function __construct($data) {
        parent::__construct(empty($data['id'])); 
        $this->post = $data;        
    }

}
