<?php

namespace CodePi\ImportExportLog\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class ImportExportLog extends BaseCommand {

    public $params;
    public $action;
    public $response;
    public $message;
    public $master_id;
    public $master_table;
    public $filename;

    function __construct($data) {

        parent::__construct(TRUE);
        $this->params = PiLib::piIsset($data, 'params', ''); //Request Params
        $this->action = PiLib::piIsset($data, 'action', ''); //Action i.e import/export
        $this->response = PiLib::piIsset($data, 'response', ''); //Response that action returns
        $this->message = PiLib::piIsset($data, 'message', ''); //Message
        $this->master_id = PiLib::piIsset($data, 'master_id', 0); //Primary key
        $this->master_table = PiLib::piIsset($data, 'master_table', ''); //Table on/from which data retrieve/insert            
        $this->filename = PiLib::piIsset($data, 'filename', ''); //Imported/Exported File Name          
    }

}
