<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Users\DataSource\ResetPassword as ResetPwdDs;
use CodePi\Users\DataSource\TrackUserPassword;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;

use CodePi\Users\Mailer\UsersMailer;

class SecurePassword implements iCommands {

    private $dataSource;
    private $objTrackPwd;

    /**
     * @ignore It will create an object of SyncUsers
     */
    public function __construct(ResetPwdDs $objAccountDS, TrackUserPassword $objTrackPwd) {
        $this->dataSource = $objAccountDS;
        $this->objTrackPwd = $objTrackPwd;
    }

    /**
     * @param object $command
     * @return arrau $result
     */
    public function execute($command) {
        $validate = $this->objTrackPwd->checkPasswordAlreadyUsed($command->id, $command->password);          
        if (!empty($validate)) {
            $status = $this->dataSource->securePassword($command);            
            return $status;
        } else {
            throw new DataValidationException('Password cannot be same as Current or Previous Passwords.', new MessageBag());
        }
    }

}
