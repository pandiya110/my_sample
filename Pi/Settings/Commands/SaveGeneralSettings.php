<?php

namespace CodePi\Settings\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class SaveGeneralSettings extends BaseCommand {

    public $id;
    public $key;
    public $value;

    function __construct($data) {
        
        parent::__construct();
        $this->id = PiLib::piIsset($data, 'id', '');
        $this->key = PiLib::piIsset($data, 'key', '');
        $this->value = (isset($data['value']) && $data['value'] == true) ? '1' :'0';
    }

}
