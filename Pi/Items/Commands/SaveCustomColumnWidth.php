<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,
    Hash,
    Crypt;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class SaveCustomColumnWidth extends BaseCommand {

    /**
     *
     * @var int
     * @access public
     */
    public $users_id;

    /**
     *
     * @var int
     * @access public
     */
    public $columns;

    /**
     * Constructor
     * @param array $data
     */
    function __construct($data) {

        parent::__construct($data);
        $this->users_id = PiLib::piIsset($data, 'users_id', 1);
        $this->columns = isset($data['columns']) ? $data['columns'] : [];
    }

}
