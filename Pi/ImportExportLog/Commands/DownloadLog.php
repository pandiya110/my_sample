<?php
namespace CodePi\ImportExportLog\Commands; 

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Libraries\MyEncrypt;

class DownloadLog extends BaseCommand { 
    
	public $id;      
        public $events_id;
        public $users_id;
        public $link;
        public $status;
        public $resid; 
        function __construct($data) { 
            $objMycrypt = new MyEncrypt; 
             $this->status = (PiLib::piIsset($data,'status', ''));
            if(isset($data['id']) && !empty($data['id'])){
                parent::__construct(TRUE);                        
                $this->id = (!empty(PiLib::piIsset($data,'id', '')))?$objMycrypt->decode($data['id']):'';             
            }else {
                $this->events_id = (!empty(PiLib::piIsset($data,'eventId', '')))?$objMycrypt->decode($data['eventId']):'';             
                $this->users_id = (!empty(PiLib::piIsset($data,'userId', '')))?$objMycrypt->decode($data['userId']):'';             
                $this->link = (PiLib::piIsset($data,'link', '')); 
                $this->resid = (PiLib::piIsset($data,'resid', '')); 
            }
	}
}


