<?php

namespace CodePi\Import\Commands;

use Request;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class UploadBulkItemsFile extends BaseCommand {

    public $filename = 'filename';
    public $size = 10 * 1024 * 1024;
    public $extensions = array(
        'xls',
        'xlsx'
    );
    public $container;
    public $files;

    function __construct($data) { // Don't change anything here.
        parent::__construct();
        $this->extensions = PiLib::piIsset($data, 'extensions', $this->extensions);
        $this->size = PiLib::piIsset($data, 'size', $this->size);
        $this->filename = PiLib::piIsset($data, 'file', $this->filename);
        $this->files = PiLib::piIsset($data, 'file', $this->files);
        $this->container = PiLib::piIsset($data, 'container', $this->container);
        $this->post = $data;
    }

}
