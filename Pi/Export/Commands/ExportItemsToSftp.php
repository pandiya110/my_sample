<?php

namespace CodePi\Export\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class ExportItemsToSftp extends BaseCommand {

    public $requireType;
    public $itemsType;
    public $cronTime;
    public function __construct($data) {
        parent::__construct($data);
        $this->requireType = PiLib::piIsset($data, 'requireType', 2);
        $this->itemsType = PiLib::piIsset($data, 'itemsType', '0');
        $this->cronTime = PiLib::piIsset($data, 'cronTime', '0');
        $this->post = $data;
    }

}
