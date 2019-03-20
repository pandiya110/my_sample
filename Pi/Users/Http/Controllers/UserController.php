<?php

namespace CodePi\Users\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;

use CodePi\Users\Commands\UsersData;
use CodePi\Users\Commands\AddPermissions;
use CodePi\Users\Commands\GetPermissions;
use CodePi\Users\Commands\GetGlobalData;
use CodePi\Users\DataSource\UsersData as UserDataDs;
use Auth;
use CodePi\Base\Eloquent\Users;
//use CodePi\Base\Eloquent\Settings;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Libraries\PiAuth;
use CodePi\Users\Commands\CreateUser;
use CodePi\Users\Commands\UserDetails;
use CodePi\Base\Libraries\PiLib;
use CodePi\Users\Commands\UpdateProfilePic;
//use CodePi\Users\Commands\GetActivationLink;
use URL;
//use CodePi\Users\Mailer\UsersMailer;
//use CodePi\Login\DataSource\Login;
use CodePi\Login\Commands\SetAuthToken;
use CodePi\Users\Commands\ResendActivationLink;
use CodePi\Users\Commands\SaveSSOUserPermissions;
use CodePi\Users\Commands\ForgotPasswordLinkValidate;
use CodePi\Users\Commands\ForgotPassword;
use CodePi\Users\Commands\ResetPasswordLink;
use CodePi\Users\Commands\ValidateAccountToken;
use CodePi\Users\Commands\ResetPassword;
use CodePi\Users\DataSource\TrackUserPassword;
use CodePi\Users\Commands\SecurePassword;
use Session;
class UserController extends PiController {

    public function __construct() {
//        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
//        header("Pragma: no-cache"); // HTTP 1.0.
//        header("Expires: 0");
    }

    /**
     * 
     * @param Request $request
     * @return type view 
     */
    public function users(Request $request) {
        
        if (Auth::check()) {
            $objRequest = $request->all();
            $userData = Users::find(Auth::user()->id)->toArray();
            $objTrackPwd = new TrackUserPassword();
            $isExpired = $objTrackPwd->getUserPasswordExp($userData['password_exp_date']);
            $userData['pwd_exp_flag'] = $isExpired; //session()->get('pwd_exp_flag');
            if (!session()->has('user_log_token')) {
                /**
                 * SetAuthToken command for generating authtoken
                 */
                $objCmd = new SetAuthToken(['users_id' => Auth::user()->id,
                    'token' => md5(Auth::user()->id . time()) . '-' . Auth::user()->id,
                    'expire_at' => date('Y-m-d H:i:s', strtotime(gmdate('Y-m-d H:i:s') . '+1 days'))
                ]);
                $userData['user_log_token'] = CommandFactory::getCommand($objCmd);
                session()->put('user_log_token', $userData['user_log_token']);
            } else {
                $userData['user_log_token'] = session()->get('user_log_token');
            }

            $userData['profile_name'] = PiLib::mbConvertEncoding(substr(trim($userData['firstname']), 0, 1) . '' . substr(trim($userData['lastname']), 0, 1));
            $ext = pathinfo($userData['profile_image_url'], PATHINFO_EXTENSION);
            if (empty($ext)) {
                $profile_image = null;
            } else {
                $fileInfo = pathinfo($userData['profile_image_url']);
                $profile_image = URL::to($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '_small.' . $fileInfo['extension']);
            }
            $userData['profile_image_url'] = $profile_image;

            $loggedAsUser = PiAuth::getLoggedAsUser();
            $command = new GetPermissions(array('users_id' => $loggedAsUser->id));
            $jsonResponse = $this->run($command, trans('Users::messages.ac_success'), trans('Users::messages.ac_failure'));
            $response = json_decode($jsonResponse->getContent());
            $accessPermissions = array();
            if (!empty($response)) {
                $accessPermissions = !empty($response->result->data->system_permissions) ? $response->result->data->system_permissions : '';
            }
            unset($userData['permissions']);
            $loggedAs = [];
            if (PiAuth::getLoggedUserId() != PiAuth::getLoggedUserId(true)) {
                $objLoggedAs = $loggedAsUser;
                $loggedAs['id'] = $objLoggedAs->id;
                $loggedAs['name'] = PiLib::mbConvertEncoding($objLoggedAs->firstname . ' ' . $objLoggedAs->lastname);
                $loggedAs['email'] = $objLoggedAs->email;
            }            
            $userData['enc_id'] = PiLib::piEncrypt($userData['id']);            
            return view('smart-app/index', ['userInfo' => $userData, 'accessPermissions' => $accessPermissions, 
                                            'loggedAs' => $loggedAs, 'user_log_token' => $userData['user_log_token'], 
                                            'pwd_exp_flag' => $userData['pwd_exp_flag']]);
        } else {
            return view('index');
        }
    }

    /**
     * Get the Users List
     * @param Request $request
     * @return type Array
     */
    public function getUsersList(Request $request) {
        $data = $request->all();
        $command = new UsersData($data);
        return $this->run($command, trans('Users::messages.UD_Success'), trans('Users::messages.UD_Failure'));
    }
    
    /**
     * save the users permissions
     * @param Request $request
     * @return type Array
     */
    public function addPermissions(Request $request) {
        $data = $request->all();
        $command = new AddPermissions($data);
        return $this->run($command, trans('Users::messages.users_permissions_success'), trans('Users::messages.users_permissions_failure'));
    }
    
    /**
     * get the list of permissions
     * @param Request $request
     * @return type array
     */
    public function getPermissions(Request $request) {
        $data = $request->all();
        $command = new GetPermissions($data);
        return $this->run($command, trans('Users::messages.ac_success'), trans('Users::messages.ac_failure'));
    }
    
    /**
     * get the user saved permissions list
     * @param Request $request
     * @return type 
     */
    public function getGlobalData(Request $request) {
        $data = $request->all();
        $command = new GetGlobalData($data);
        return $this->run($command, trans('Users::messages.ac_success'), trans('Users::messages.ac_failure'));
    }
    
    /**
     * save the user informations
     * @param Request $request
     * @return type response
     */
    public function addUser(Request $request){
        $data = $request->all();
        $command = new CreateUser($data);
        return $this->run($command, trans('Users::messages.S_CreateUser'), trans('Users::messages.E_CreateUser'));
    }
    /**
     * Get the user informations
     * @param Request $request     
     * @return type 
     */
    public function getUserDetails(Request $request){
        $data = $request->all();
        $command = new UserDetails($data);
        return $this->run($command, trans('Users::messages.UD_Success'), trans('Users::messages.UD_Failure'));
    }
    /**
     * Upload image for users profile logo
     * @param Request $request
     * @return type
     */
    public function uploadLogo(Request $request){
        $data = $request->all();
        $command = new UpdateProfilePic($data);
        return $this->run($command, trans('Users::messages.UD_Success'), trans('Users::messages.UD_Failure'));
        
    }
    
    /**
     * sending activation link to User
     * @param Request $request Id as string
     * @param type $id
     * @return type view
     */
    function getActivationLink(Request $request, $id) {
        \DB::beginTransaction();
        try {
            $data = $request->all();
            $userId = PiLib::piDecrypt($id);
            $objUser = new Users();
            $userData = $objUser->where('id', $userId)->first();
            $acitvateExptime = strtotime($userData->activate_exp_time);
            $currentTime = time();
           
            if ($userData->status == '1' && $userData->is_register == '0' && $acitvateExptime > $currentTime) {
                $data = ['is_register' => '1', 'id' => $userId];
                $resultSatus = $objUser->saveRecord($data);
                \DB::commit();
                return view('activation', ['email_id' => $userData->email]);
            } else {
                return view('errors/404');
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            \DB::rollback();
        }
     }

    public function addTestUser(Request $request){
        $data = $request->all();
        $obj = new UserDataDs();
        $obj->addTestUser();
    }
    
    /**
     * Re-send user activations link
     * @param Request $request
     * @param type $email_id
     * @return type
     */
    public function resendActivationLink(Request $request) {
        $data = $request->all();
        $command = new ResendActivationLink($data);
        return $this->run($command, trans('Users::messages.S_ResendLink'), trans('Users::messages.E_ResendLink'));
    }
    /**
     * Add role based permissions for sso users
     * @param Request $request
     * @return Response
     */
    public function saveSSOUserPermissions(Request $request){
        $data = $request->all();
        $command = new SaveSSOUserPermissions($data);
        return $this->run($command, trans('Users::messages.S_SSOPermissions'), trans('Users::messages.E_SSOPermissions'));
    }
    
     
     /** validation of forgot password link
     * 
     * @param Request $id
     * @param Request $token
     * @return status success|failure
     */
    public function forgotPasswordLinkValidate(Request $request) {

        $data = $request->all();
        $id = $data['id'];
        $token = $data['token'];
        $command = new ForgotPasswordLinkValidate($id, $token);
        return $this->run($command, trans('Login::messages.S_ForgotPasswordLinkValidation'), trans('Login::messages.E_ForgotPasswordLinkValidation'));        
    }

    public function forgotPassword(Request $request) {
        
        $data = $request->all();        
        $command = new ForgotPassword($data);
        return $this->run($command, trans('Login::messages.S_ForgotPassword'), trans('Login::messages.E_ForgotPassword'));
    }

    /**
     *  @param Request $id
     *  @param Request $token
     *  @param Request $newPassword
     *  @param Request $confirmPassword
     */
    public function resetPasswordLink(Request $request) {
        $data = $request->all();
        $command = new ResetPasswordLink($data);
        return $this->run($command, trans('Login::messages.S_ResetPasswordLink'), trans('Login::messages.E_ResetPasswordLink'));
    }
    /**
     * 
     * @param Request $request
     * @return type
     * @throws \Exception
     */
    function createPassword(Request $request) {
        $data = $request->all();
        
        try {
            $objCommand = new ValidateAccountToken($data);
            $arrResponse = CommandFactory::getCommand($objCommand);
            
            if (!isset($arrResponse['status'])) {
                throw new \Exception('Error Occured');
            }
        } catch (\Exception $e) {
            $arrResponse['status'] = false;
            $arrResponse['message'] = 'System error occurred';
            $arrResponse['error'] = $e->getMessage();
        }
        $arrResponse['tp'] = isset($data['tp']) ? $data['tp'] : 'r';
        $arrResponse['enc_id'] = isset($data['id']) ? $data['id'] : 0;
        $arrResponse['redirect'] = url('/login');
        if ($arrResponse['tp'] == 'ac') {
            if ($arrResponse['message'] == 'already_used') {
                $arrResponse['message'] = 'We are sorry this link is expired or has already been used';
            }
        } else {
            if ($arrResponse['message'] == 'already_used') {
                $arrResponse['message'] = 'We are sorry this link is expired or has already been used';
            }
        }
        
//        if ($arrResponse['status'] == false) {
//            return view('errors/404', ['info' => json_encode($arrResponse)]);
//        }
        /**
         * Get User informations
         */
        $userCommand = new UsersData(['id' => PiLib::piDecrypt($arrResponse['enc_id'])]);
        $userInfo = CommandFactory::getCommand($userCommand);
        $userInfo = isset($userInfo['items']) && !empty($userInfo['items']) ? array_shift($userInfo['items']) : []; 
        
        return view('password', ['info' => json_encode($arrResponse), 'userInfo' => $userInfo]);
    }
    /**
     * 
     * @param Request $request
     * @return type
     */
    function resetPassword(Request $request) {
        $data = $request->all();
        $objCommand = new ResetPassword($data);
        return $this->run($objCommand, 'Password changed successfully', 'Failure to change the password');
    }
    /**
     * 
     * @param Request $request
     * @return type
     */
    function securePassword(Request $request) {
        $data = $request->all();
        $objCommand = new SecurePassword($data);
        $cmdResponse = $this->run($objCommand, 'Password changed successfully', 'Failure to change the password');
        $jsonResponse = json_decode($cmdResponse->content(), true);
        if (isset($jsonResponse['result']['data']) && $jsonResponse['result']['data'] === true) {
            Auth::logout();
            Session::flush();
            return $jsonResponse;
        } else {
            return $jsonResponse;
        }
    }

}
