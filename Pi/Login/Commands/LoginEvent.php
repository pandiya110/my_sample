<?php

namespace CodePi\Login\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;
class LoginEvent extends BaseCommand{

	protected $users_id;
	protected $screen_width;
	protected $screen_height;
        protected $date_added;
	protected $gt_date_added;
	protected $created_by;
	protected $instances_id;
	protected $last_modified;
	protected $last_modified_by;
	protected $ip_address;
	protected $gt_last_modified;
	function __construct($data) {
           parent::__construct(TRUE);
           $this->users_id =PiLib::piIsset($data,'users_id', '');
           $this->screen_width =PiLib::piIsset($data,'screen_width', '');
           $this->screen_height =PiLib::piIsset($data,'screen_height', ''); 
           $this->post = $data;
	}
}
