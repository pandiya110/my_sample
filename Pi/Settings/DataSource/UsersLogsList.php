<?php

namespace CodePi\Settings\DataSource;

use CodePi\Settings\Mailer\DataControllersMailer;
use CodePi\Base\Eloquent\UsersLog;
use CodePi\Base\Eloquent\Users;
use DB;
use CodePi\Base\Libraries\PiLib;

class UsersLogsList {

    /**
     * get all users_log details. 
     * @param $data
     * @return array $users
     */
    function getUsersLogs($command) {
        $data = $command->dataToArray();
        $data['limit'] = '';
        if (isset($data['page'])) {
            $data['limit'] = ($data['page'] - 1) * $data['perPage'];
        }

        //return $emailControllers =$this->model->skip($data['limit'])->take($data['perPage'])->get();
        $userLogs = UsersLog::join('users as u', 'u.id', '=', 'users_logs.users_id')
                ->select('users_logs.*')
                ->selectRaw('concat(COALESCE(u.firstname,\'\'), \' \', COALESCE(u.lastname,\'\')) as name')
                ->skip($data['limit'])
                ->take($data['perPage'])
                ->orderBy($data['order'], $data['sort'])
                ->get();
                // print_r($userLogs->toArray());die;
        // if (!empty($userLogs)) {
        //     $objPiLib = new PiLib;
        //     foreach ($userLogs as $key => $val) {
        //         $userLogs[$key]->login_time = ($val->login_time === NULL or empty($val->login_time) ) ? "" : $objPiLib->getUserTimezoneDate($val->login_time, 'M d, Y h:i A');
        //         $userLogs[$key]->logout_time = ($val->logout_time === NULL or empty($val->logout_time)) ? "" : $objPiLib->getUserTimezoneDate($val->logout_time, 'M d, Y h:i A');
        //     }
        // }
        // print_r($userLogs->toArray());die;
        return $userLogs;
    }

    function getLogsCount() {
        return UsersLog::count();
    }

}
