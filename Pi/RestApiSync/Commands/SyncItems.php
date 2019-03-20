<?php

namespace CodePi\RestApiSync\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class SyncItemsChannels extends BaseCommand {
    

    public function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->post = $data;
    }

}
