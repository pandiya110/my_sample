<?php

namespace CodePi\Channels\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class AddChannels extends BaseCommand {
    /**
     *
     * @var integer
     */
    public $id;
    /**
     *
     * @var string
     */
    public $name;
    //public $channel_logo;
    /**
     *
     * @var enum
     */
    public $status;
    /**
     *
     * @var string
     */
    public $description;
    /**
     * Prepare post params to create/update channels
     * @param array $data['id']             = (int) This params will be empty while create new channels
     *                   ['name']           = (string) Channels Name
     *                   ['description']    = (string) Channels Decription
     *                   ['status']         = (boolean) True or False change the channels active status   
     */
    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->id = PiLib::piIsset($data, 'id', '');
        $this->name = PiLib::piIsset($data, 'name', '');
        //$this->channel_logo = PiLib::piIsset($data, 'channel_logo', '');
        $this->description = PiLib::piIsset($data, 'description', '');
        $this->status = (isset($data['status']) && $data['status'] == true) ? '1' : '0';
        $this->post = $data;
    }

}
