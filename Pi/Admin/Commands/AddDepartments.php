<?php

namespace CodePi\Admin\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,
    Hash,
    Crypt;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class AddDepartments extends BaseCommand {

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
     * @var string
     */
    public $description;

    /**
     *
     * @var boolean 
     */
    public $status;

    /**
     * Assign the post value for add/update department
     * @param type $data
     */
    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->id = PiLib::piIsset($data, 'id', '');
        $this->name = PiLib::piIsset($data, 'name', '');
        $this->description = PiLib::piIsset($data, 'description', '');
        $this->status = (isset($data['status']) && $data['status'] == true) ? '1' : '0';
    }

}
