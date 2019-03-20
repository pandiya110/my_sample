<?php

namespace CodePi\Attachments\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class UploadAttachment extends BaseCommand {

    public $filename = 'filename';
    public $size = 20 * 1024 * 1024;
    public $extensions = array(
        'jpeg',
        'jpg',
        'PNG',
        'png',
        'GIF',
        'gif'
    );

    public $container;
    public $files;

    function __construct($data) { // Don't change anything here.
        parent::__construct();
        $this->extensions = PiLib::piIsset($data,'extensions',$this->extensions);
        $this->size = PiLib::piIsset($data,'size',$this->size);
        $this->filename = PiLib::piIsset($data,'filename',$this->filename);
        $this->files = PiLib::piIsset($data,'files',$this->files);
        $this->container = PiLib::piIsset($data,'container',$this->container);
        $this->post =$data;
    }
    
}
