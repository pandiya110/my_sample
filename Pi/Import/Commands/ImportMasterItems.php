<?php

namespace CodePi\Import\Commands; 

use Request;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class ImportMasterItems extends BaseCommand {

    public $filename;

    /**
     * 
     * @param array $data
     */
    function __construct($data) {
        parent::__construct();
        $this->filename = PiLib::piIsset($data, 'file', '');
    }

}
