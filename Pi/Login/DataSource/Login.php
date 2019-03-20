<?php

namespace CodePi\Login\DataSource;

use CodePi\Base\Eloquent\Users;
use CodePi\Login\DataTranslators\LoginTranslators;
use CodePi\Base\DataSource\DataSource;
use Auth,
    Session;
use App\User;
use Illuminate\Http\Request;
use CodePi\Base\Libraries\Agent\BrowserAgent;
use CodePi\Base\Libraries\MyEncrypt;
use CodePi\Base\Eloquent\UsersLog;
use App\Events\UserlogInfoEvent;
use App\Events\UserLogoutEvent;
use CodePi\Base\Eloquent\UsersAuthTokens;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
use CodePi\Users\DataSource\TrackUserPassword;
class Login {


    /**
     * Getting user login
     * @param array $params
     * @param array element $username     
     * @return boolean $status true|false 
     */
//    function loginUser($params) {
//        $remember = false;
//        if (isset($params ['remember'])) {
//            $remember = true;
//        }
//        $response = array("status" => false);
//        
//        $objUser = Users::where('email', $params['username'])->where('status',true)->where('is_register', true)->first();
//        if (!empty($objUser)) {
//            Auth::login($objUser);            
//            if (!empty($objUser)) {
//                $params['user_id'] = Auth::user()->id;                                
//                event(new UserlogInfoEvent($params));
//                $response = array("status" => true);
//            }
//            if($response['status'] == true){
//                Session::put('timezone_offset', $params['timezone']);
//                //$response['auth_token'] = $this->setAuthToken(Auth::user()->id);
//            }
//        }
//        
//        return $response;
//    }
    /**
     * Login With Username/Password
     * @param type $params
     * @return boolean
     */
    function loginUser($params) {
        $remember = false;
        if (isset($params ['remember'])) {
            $remember = true;
        }
        $response = array("status" => false);
        if (Auth::attempt(['email' => trim($params ['username']), 'password' => $params ['password'], 'status' => true], $remember)) {
            $params['user_id'] = Auth::user()->id;
            event(new UserlogInfoEvent($params));
            $data = ['login_from_sso' => '1', 'id' => Auth::user()->id];
            $objUser = new Users();
            $objUser->saveRecord($data);
            $userData = Users::find(Auth::user()->id)->toArray();
            $objTrackPwd = new TrackUserPassword();
            $isExpired = $objTrackPwd->getUserPasswordExp($userData['password_exp_date']); //1 => Reminder; 2 => Expired;
            //Session::put('pwd_exp_flag', $isExpired);
            /**
             * Set timezone in session
             */
            Session::put('timezone_offset', $params['timezone']);
            $response = array("status" => true, 'pwd_exp_flag' => $isExpired);
        } else {
            throw new DataValidationException('You have entered an invalid Username or Password.', new MessageBag());
        }

        return $response;
    }

    /**
     * Getting user logout
     * @return boolean $response true|false
     */
    public function logout($params = array()) {        
        $logginDetails = event(new UserLogoutEvent());
        Auth::logout();
        Session::flush();
        return $response = array("status" => true);
    }

    /** saving user logut time
     * 
     
    function logoutDetails() {
        $log_id = Session::get('login_id');
        if (!empty($log_id)) {
            $objUsersLog = new UsersLog();
            $objUsersLog->saveRecord(['id' => $log_id, 'logout_time' => date('Y-m-d H:i:s')]);
        }
        return $log_id;
    }*/
    
    /**
     * Set User Auth Token
     * @param array $params
     * @return string
     */
    public function setAuthToken($params) {
        
        $objUserAuthToken = new UsersAuthTokens();
        $token = 0;
        $objUserAuthToken->dbTransaction();
        try {
            $saveDetails = $objUserAuthToken->saveRecord($params);
            $objUserAuthToken->dbCommit();
            $token = $saveDetails->token;
        } catch (\Exception $ex) {
            $objUserAuthToken->dbRollback();
        }
        return $token;
    }

    /**
     * Get Token by user id
     * @param string $token
     * @return int
     */
    public function getUserIdByToken($params) {
        
        $currentDateTime = gmdate("Y-m-d H:i:s");
        $objUserAuthToken = new UsersAuthTokens();
        $userAuthObj = $objUserAuthToken->where('token', $params['token'])
                                        ->where('expire_at', '>', $currentDateTime)->first();
        return !empty($userAuthObj) ? $userAuthObj->users_id : 0;
    }

}
