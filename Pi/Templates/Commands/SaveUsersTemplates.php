<?php

namespace CodePi\Templates\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class SaveUsersTemplates extends BaseCommand {
    /**
     *
     * @var int 
     */
    public $users_id;
    /**
     *
     * @var string 
     */
    public $name;
    /**
     *
     * @var int 
     */
    public $id;
    /**
     *
     * @var enum 
     */
    public $is_active;
    /**
     *
     * @var array
     */
    public $columns;
    /**
     * 
     * @param array $data
     */
    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->users_id = PiLib::piIsset($data, 'users_id', "");
        $this->name = PiLib::piIsset($data, 'name', "");
        $this->id = PiLib::piIsset($data, 'id', "");
        $this->is_active = !empty($data['is_active']) ? '1' : '0';
        $this->columns = PiLib::piIsset($data, 'columns', []);
    }

}
