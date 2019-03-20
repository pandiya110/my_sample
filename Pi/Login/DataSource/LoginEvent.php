<?php

namespace CodePi\Login\DataSource;

use CodePi\Login\Commands\LoginEvent as LoginEventCommand;
use CodePi\Base\Libraries\Agent\BrowserAgent;
use CodePi\Base\Eloquent\UsersLog;
use Session;

class LoginEvent {

    /** storing login details
     * 
     * @param object $command
     */
    public function loggingDetails(LoginEventCommand $command) {
        $objUsersLog = new UsersLog();
        $objUsersLog->dbTransaction();
        try {
            $params = $command->dataToArray();
            $objBrowserAgent = new BrowserAgent;
            $device_details = $objBrowserAgent->getDetails();
            $userDetails = array(
                'users_id' => $params['user_id'],
                'login_time' => date('Y-m-d H:i:s'),
                'screen' => isset($params['screen_width']) ? $params['screen_width'] : "" . ' X ' . isset($params['screen_height']) ? $params['screen_height'] : ""
            );

            $createdInfo = $command->getCreatedInfo();
            $objLog = $objUsersLog->saveRecord(array_merge($device_details, $userDetails, $createdInfo));
            $log_id = $objLog['id'];
            $objUsersLog->dbCommit();
            Session::put('login_id', $log_id);
        } catch (\Exception $ex) {
            $objUsersLog->dbRollback();
        }
    }

  

}
