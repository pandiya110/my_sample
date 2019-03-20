<?php

namespace CodePi\Attachments\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

class AddAttachment extends BaseCommand {

    public $id;
    public $db_name;
    public $original_name;
    public $screen_name;
    public $status='local'; // local - keep files in local folder, cloud - Move to cloud imediately
    public $process_status='0'; //0 intial, 1 - process , 2 - completed/no process require, 3 - Exceptions
    public $local_img_process='true'; // true - do image process inlocal system, false - do image conversion in process server
    public $local_to_cloud='false'; // if status = local && local_to_cloud = true - Move to cloud via cron after some time,
    public $resolution_name;
    public $imageExtensions = array(
        'jpeg',
        'jpg',
        'png',
        'gif',
        'bmp',
    );
    function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->id = PiLib::piIsset($data, 'id', 0);
        $this->isAuto=true;
        $this->status = PiLib::piIsset($data,'status',$this->status);
        $this->local_img_process = PiLib::piIsset($data,'local_img_process',$this->local_img_process);
        $this->local_to_cloud = PiLib::piIsset($data,'local_to_cloud',$this->local_to_cloud);
        $this->imageExtensions = PiLib::piIsset($data,'imageExtensions',$this->imageExtensions);
        $this->resolution_name = PiLib::piIsset($data,'resolution_name','');
        $this->db_name = PiLib::piIsset($data,'db_name','');
        $this->original_name = PiLib::piIsset($data,'original_name','');
        $this->post =$data;
        
    }

}
