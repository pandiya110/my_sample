<?php

namespace CodePi\Users\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class UpdateProfilePic extends BaseCommand {

//    public $id;
//    public $file;
//    
//    function __construct($data) {
//       parent::__construct(empty($data['id']));
//       $this->id =PiLib::piIsset($data,'id', 0);
//       $this->file =PiLib::piIsset($data,'file', ''); 
//    }
    
    public $size = 10 * 1024 * 1024;
    public $container;
    public $file;

    function __construct($data) { // Don't change anything here.
        parent::__construct();       
        $this->size = PiLib::piIsset($data, 'size', $this->size);        
        $this->file = PiLib::piIsset($data, 'file', $this->file);
        $this->container = PiLib::piIsset($data, 'container', $this->container);
        $this->post = $data;
    }

}
