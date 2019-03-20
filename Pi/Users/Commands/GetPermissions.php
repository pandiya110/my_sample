<?php

namespace CodePi\Users\Commands;


use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

/**
 * @ignore It will reveals the table fields and fectech the input data to database fields
 */
class GetPermissions extends BaseCommand {

    public $profile_id;
    public $users_id ;
    public $roles_id ;


    public function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->users_id = PiLib::piIsset($data, 'users_id', 0);
        $this->roles_id = PiLib::piIsset($data, 'roles_id', 0);
        $this->post = $data;
        
    }

}
