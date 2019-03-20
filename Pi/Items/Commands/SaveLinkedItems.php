<?php
namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,Hash,Crypt;  
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class SaveLinkedItems extends BaseCommand{  
	
    public $id;    
    public $postData;
    /**
     * 
     * @param array $data
     */
    function __construct($data) {

        parent::__construct(empty($data['id']));        
        $this->postData = PiLib::piIsset($data, 'postData', []);
        
    }

}
