<?php

namespace CodePi\Import\Commands;

use Request;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class ImportBulkData extends BaseCommand {

    public $filename;
    public $event_id;    
 

    /**
     * 
     * @param type $data
     */
    function __construct($data) {
        parent::__construct();
        $this->filename = PiLib::piIsset($data, 'filename', '');
        $this->event_id = isset($data['event_id']) ? PiLib::piDecrypt($data['event_id']) : 0;        
        $this->post = $data;
    }

}
