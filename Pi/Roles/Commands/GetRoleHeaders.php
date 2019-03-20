<?php

namespace CodePi\Roles\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,
    Hash,
    Crypt;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetRoleHeaders extends BaseCommand {

    /**
     *
     * @var int 
     */
    public $roles_id;

    /**
     * 
     * @param type $data
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->roles_id = PiLib::piIsset($data, 'id', 0);
    }

}
