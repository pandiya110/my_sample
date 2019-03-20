<?php

namespace CodePi\Login\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;
class Login extends BaseCommand {

    protected $username;
    protected $password;
    protected $date_added;
    protected $gt_date_added;
    protected $created_by;
    protected $instances_id;
    protected $last_modified;
    protected $last_modified_by;
    protected $ip_address;
    protected $gt_last_modified;
    protected $remember;
    protected $timezone;
    protected $screen_width;
    protected $screen_height;

    function __construct($data) {
        $this->username = PiLib::piIsset($data, 'username', '');
        $this->password =PiLib::piIsset($data,'password', '');
        $this->remember = PiLib::piIsset($data, 'remember', '');
        $this->timezone = PiLib::piIsset($data, 'timezone', '');
        $this->screen_width = PiLib::piIsset($data, 'screen_width', '');
        $this->screen_height = PiLib::piIsset($data, 'screen_height', '');
    }

}
