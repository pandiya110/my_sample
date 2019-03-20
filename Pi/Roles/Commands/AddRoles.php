<?php

namespace CodePi\Roles\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class AddRoles extends BaseCommand {
    /**
     *
     * @var int
     */
    public $id;
    /**
     *
     * @var string
     */
    public $name;
    /**
     *
     * @var boolean
     */
    public $status;
    /**
     *
     * @var string
     */
    public $description;
    /**
     * 
     * @param array $data
     */
    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->id = PiLib::piIsset($data, 'id', '');
        $this->name = PiLib::piIsset($data, 'name', '');
        $this->description = PiLib::piIsset($data, 'description', '');
        $this->status = (isset($data['status']) && $data['status'] == true) ? '1' : '0';
        $this->post = $data;
    }

}
