<?php

namespace CodePi\Users\DataSource;

use CodePi\Base\Eloquent\UsersPassword;
use Hash;
use DB;
use Exception;
use Request;
class TrackUserPassword {

    /**
     * 
     * @param type $intUserId
     * @param type $password
     * @return boolean
     */
    function trackUserPassword($intUserId = 0, $password) {
        DB::beginTransaction();
        try {
            if (!empty($intUserId) && !empty($password)) {
                
                $objUserPwd = new UsersPassword();
                $passwordCnt = $objUserPwd->where('users_id', $intUserId)->count();
                if ($passwordCnt == 6) {
                    $oldPwd = $objUserPwd->where('users_id', $intUserId)->orderBy('date_added')->limit(1)->first();
                    $data['users_password'] = $password;
                    $data['date_added'] = gmdate('Y-m-d H:i:s');
                    //$data['ip_address'] = Request::getClinetIp();
                    $data['id'] = $oldPwd->id;
                    $objUserPwd->saveRecord($data);
                } else {
                    $data['users_id'] = $intUserId;
                    $data['users_password'] = $password;
                    $data['date_added'] = gmdate('Y-m-d H:i:s');
                    //$data['ip_address'] = Request::getClinetIp();
                    $objUserPwd->saveRecord($data);
                }
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
        }

        return true;
    }
    /**
     * 
     * @param type $pwd_exp_date
     * @return int
     */
    function getUserPasswordExp($pwdExpDt) {
        $isExpired = 0;
        $reminderDays = $pwdExpDt != '' ? strtotime($pwdExpDt) - (86400 * 15) : 0;
        if ($pwdExpDt != '' && strtotime($pwdExpDt) <= strtotime(gmdate('Y-m-d H:i:s'))) {
            $isExpired = 2;
        } else if ($pwdExpDt != '' && $reminderDays <= strtotime(gmdate('Y-m-d H:i:s'))) {
            $isExpired = 1;
        }
        return $isExpired;
    }
    /**
     * 
     * @param type $intUserId
     * @param type $password
     * @return boolean
     */
    function checkPasswordAlreadyUsed($intUserId = 0, $password) {
        if (!empty($intUserId) && !empty($password)) {
            $check = true;
            $objUserPwd = new UsersPassword();
            $passwordCnt = $objUserPwd->where('users_id', $intUserId)->get(['users_password'])->toArray();
            foreach ($passwordCnt as $pwd) {
                if (Hash::check($password, $pwd['users_password'])) {
                    $check = false;
                    break;
                }
            }
            return $check;
        } else {
            throw new Exception('Password cannot be empty or Users id..!');
        }
    }

}
