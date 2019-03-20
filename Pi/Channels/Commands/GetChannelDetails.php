<?php

namespace CodePi\Channels\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetChannelDetails extends BaseCommand {

    /**
     *
     * @var int 
     */
    public $id;
    /**
     * Prepare post params to get the channels details
     * @param array $data['id'] = (int) This is a params to get the particular channels info
     */
    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->id = PiLib::piIsset($data, 'id', 0);
    }

}
