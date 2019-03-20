<?php

namespace CodePi\Login\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

/**
 * @ignore It will reveals the table fields and fectech the input data to database fields
 */
class CreateUserLogs extends BaseCommand {

    public $screen_width;
    public $screen_height;

    public function __construct($data) {
    	parent::__construct();
    	$this->screen_width= PiLib::piIsset($data,'screen_width',0);
    	$this->screen_height= PiLib::piIsset($data,'screen_height',0);
    	// $this->post= $data;
    }

}
