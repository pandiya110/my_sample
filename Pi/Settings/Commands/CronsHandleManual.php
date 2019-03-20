<?php

namespace CodePi\Settings\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class CronsHandleManual extends BaseCommand {

    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->cron_code = empty(PiLib::piIsset($data, 'cron_code', '')) ? '' : $data['cron_code'];
        $this->post = $data;

    }

}
