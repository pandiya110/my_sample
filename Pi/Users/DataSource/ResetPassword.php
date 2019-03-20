<?php

namespace CodePi\Users\DataSource;

use CodePi\Users\Commands\ValidateAccountToken;
use CodePi\Users\Commands\ResetPassword AS ResetPasswordCmd;
use CodePi\Base\Mailer\UserForgotTokens;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Eloquent\Users;
use Hash;
use DB;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
use CodePi\Users\DataSource\TrackUserPassword;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Users\Commands\SecurePassword AS SecurePasswordCmd;
/**
 * handle the Sync users
 */
class ResetPassword {

    function validateAccountToken(ValidateAccountToken $command) {
        $params = $command->dataToArray();
        $encid = $params['id'];
        $token = $params['token'];
        $message = 'valid';


        $objUserForgotTokens = new UserForgotTokens ();
        $status = false;

        if (!empty($encid)) {
            $enc_id = PiLib::piDecrypt($encid);
            if (is_numeric($enc_id)) {

                $result = $objUserForgotTokens->checkResetTokenId($enc_id, $token);

                if (!empty($result)) {
                    $current_timestamp = time();
                    if ($result->valid_upto > $current_timestamp) {
                        $status = true;
                    } else {
                        $message = 'We are sorry this link is expired or has already been used';
                    }
                } else {
                    $message = 'already_used';
                }
            }
        }

        return [
            'enc_id' => $enc_id,
            'token' => $token,
            'status' => $status,
            'message' => $message
        ];
    }

    function resetPassword(ResetPasswordCmd $command) {
        $user = [];
        DB::beginTransaction();
        try {
            $params = $command->dataToArray();

            $objUser = new Users();

            $params['password'] = Hash::make($params['password']);  //md5($params['password']);
            $params['is_register'] = '1';
            $params['activate_exp_time'] = date('Y-m-d H:i:s', strtotime(gmdate('Y-m-d H:i:s') . '+2 days'));
            $params['password_exp_date'] = date('Y-m-d H:i:s', strtotime(gmdate('Y-m-d H:i:s') . '+75 days'));
            $oldValues = $objUser->where('id', $params['id'])->first()->toArray();
            /**
             * Update Password
             */
            $user = $objUser->saveRecord($params);
            /**
             * Save Forgot password token
             */
            if (isset($params['token']) && !empty($params['token'])) {
                $objToken = new UserForgotTokens();
                $objToken->updateForgotTokens($user->id, $params['token']);
            }
            /**
             * Track the users passwords
             */
            $objTrackPwd = new TrackUserPassword();
            $objTrackPwd->trackUserPassword($params['id'], $params['password']);
            $currentValues = $objUser->where('id', $params['id'])->first()->toArray();
            
            DB::commit();            
        } catch (\Exception $ex) {
            DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            throw new DataValidationException($ex->getMessage(), new MessageBag());
        }
        
        return $user;
        
    }
    /**
     * 
     * @param SecurePasswordCmd $command
     * @return boolean
     */
    function securePassword(SecurePasswordCmd $command) {
        $status = false;
        DB::beginTransaction();
        try {
            $params = $command->dataToArray();

            $objUser = new Users();
            $params['password'] = Hash::make($params['password']);
            $params['password_exp_date'] = date('Y-m-d H:i:s', strtotime(gmdate('Y-m-d H:i:s') . '+75 days'));            
            /**
             * Update Password
             */
            $user = $objUser->saveRecord($params);
            /**
             * Track the users passwords
             */
            $objTrackPwd = new TrackUserPassword();
            $objTrackPwd->trackUserPassword($params['id'], $params['password']);
            $status = true;
            DB::commit();            
        } catch (\Exception $ex) {
            $status = false;
            DB::rollback();
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);            
        }
        return $status;
    }

}
