<?php

namespace CodePi\Channels\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class GetChannelsAdtypes extends BaseCommand {

    /**
     *
     * @var Integer 
     */
    public $channels_id;
    /**
     *
     * @var Integer
     */
    public $items_id;

    /**
     * 
     * @param array $data ['channels_id']   = (int)This is a ID to get the particular channels ad types
     *                    ['items_id']      = (int)This is a ID to get particular items row channels & Ad types           
     */
    public function __construct($data) {

        parent::__construct(empty($data['id']));

        $this->channels_id = PiLib::piIsset($data, 'channels_id', 0);
        $this->items_id = PiLib::piIsset($data, 'items_id', 0);
    }

}
