<?php

namespace CodePi\Users\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;

/**
 * @ignore It will reveals the table fields and fectech the input data to database fields
 */
class SecurePassword extends BaseCommand {

    public $password;
    public $newPassword;
    public $id;
    public $currentPassword;
    
    public function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->id = PiLib::piDecrypt($data['id']);
        $this->password = PiLib::piIsset($data, 'password', '');
        $this->newPassword = PiLib::piIsset($data, 'newPassword', '');
        $this->currentPassword = PiLib::piIsset($data, 'currentPassword', '');
    }

}
