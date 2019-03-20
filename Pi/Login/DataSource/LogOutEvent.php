<?php

namespace CodePi\Login\DataSource;

use Session;
use CodePi\Base\Eloquent\UsersLog;
use CodePi\Login\Commands\LogOutEvent as LogOutEventCommand;
class LogOutEvent {

    

    /**
     * Getting user logout
     * @return boolean $status true|false
     */
    
   public function logoutDetails(LogOutEventCommand $command) {
        $log_id = Session::get('login_id');
        $objUsersLog = new UsersLog();
        $objUsersLog->dbTransaction();
        try {
            if (!empty($log_id)) {

                $objUsersLog->saveRecord(['id' => $log_id, 'logout_time' => date('Y-m-d H:i:s')]);
            }
            $objUsersLog->dbCommit();
        } catch (\Exception $ex) {
            $objUsersLog->dbRollback();
        }

        return $log_id;
    }
    

}
