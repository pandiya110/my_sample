<?php

namespace CodePi\Roles\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetRolesDetails extends BaseCommand {

    /**
     *
     * @var int 
     */
    public $id;
    /**
     * 
     * @param type $data
     */
    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->id = PiLib::piIsset($data, 'id', 0);
        $this->post = $data;
    }

}
