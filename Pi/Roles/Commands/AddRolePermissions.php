<?php

namespace CodePi\Roles\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class AddRolePermissions extends BaseCommand {
    /**
     *
     * @var array
     */
    public $permissions;
    /**
     * 
     * @param array $data
     */
    public function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->post = $data;
    }

}
