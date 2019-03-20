<?php

namespace CodePi\Users\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class SaveSSOUserPermissions extends BaseCommand {

    public $id;
    public $departments_id;
    public $roles_id;

    function __construct($data) {


        parent::__construct(empty($data['id']));
        $this->id = PiLib::piIsset($data, 'id', '');
        $this->departments_id = PiLib::piIsset($data, 'department', '');
        $this->roles_id = PiLib::piIsset($data, 'roles_id', 0);
    }

}
