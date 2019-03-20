<?php

namespace CodePi\Channels\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class UploadChannelLogo extends BaseCommand {
    /**
     *
     * @var string
     */
    public $size = 10 * 1024 * 1024;
    /**
     *
     * @var string 
     */
    public $container;
    /**
     *
     * @var FILE
     */
    public $file;

    function __construct($data) { // Don't change anything here.
        parent::__construct();
        $this->size = PiLib::piIsset($data, 'size', $this->size);
        $this->file = PiLib::piIsset($data, 'file', $this->file);
        $this->container = PiLib::piIsset($data, 'container', $this->container);
        $this->post = $data;
    }

}
