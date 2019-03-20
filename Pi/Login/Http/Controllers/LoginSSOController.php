<?php

namespace CodePi\Login\Http\Controllers;

use CodePi\Base\Http\PiController;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use CodePi\Login\Commands\CreateUserLogs;
use CodePi\Login\Commands\UserLogoutDetails;
use Auth,Session,
    App\User;
use App\Events\UserlogInfoEvent;

class LoginSSOController extends PiController {

    protected $provider;

    public function __construct() {
        $this->provider = app()->make('oauth2cli');
    }

    /**
     * @return redirect to SSO login Url
     */
    public function getLoginSsoUrl() {

        return redirect()->to($this->provider->getLoginUrl());
    }

    /**
     * Recieve the Athorization code and get the access token
     * @param object Request $request
     * @return type URL to home page
     */
    public function doLoginSso(Request $request) {

        //check the state param which client has generated and received from SSO is same or not
        if (session()->has('oauth2state') && session()->get('oauth2state') == $request->get('state')) {
            // access token will be accessed from sso server and will be stored in local session to access the resource from SSO
            $data = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->get('code')
            ]);

            $userInfo = $data['id_token']; //$this->provider->getUserInfo();  
            $userInfoArr = explode('.', $userInfo);
            $base64_data = base64_decode($userInfoArr[1]);
            //echo "<pre>";  print_r($base64_data);die;
            $objUserInfo = json_decode($base64_data);
            // echo "<pre>";  print_r($objUserInfo);die;
            if (isset($objUserInfo->email)) {
                $data = ['email' => $objUserInfo->email];
                Auth::login(User::firstOrCreate($data));
                $objUser = User::find(Auth::user()->id);
                $name = explode(' ', $objUserInfo->name);
                $lastname = count($name) > 1 ? ($name[1]) : '';
                // array_pop($name);
                $firstname = count($name) > 0 ? $name[0] : '';
                $objUser->firstname = $firstname;
                $objUser->lastname = $lastname;
                $objUser->profile_id = 1;
                $objUser->login_from_sso = '2';
                //$objUser->profile_image_url = $objUserInfo->result->data->image;
                //$objPermissions = json_decode($this->provider->getUserPermission($objUserInfo->result->data->profile_id));
                //$objUser->permissions = json_encode($objPermissions->result->data);
                //$objUser->recon_users_id = $objUserInfo->result->data->users_id;
                $objUser->save();

                //session()->put('is_logged_by_sso', true);
                //Session::set('timezone_offset', $objUserInfo->result->data->timezone_offset);
                //$command = new  CreateUserLogs($request->all());
                //$this->run($command,trans('Login::messages.S_CreateUserLogs'),trans('Login::messages.E_CreateUserLogs'));
                $params['user_id'] = $objUser->id;
                event(new UserlogInfoEvent($params));
                $response = array("status" => true);

                //redirect('listbuilder/events');
            }
        } else {
            return 'Invalid state or state session has expired: Login again <a href="' . url("/login") . '">Click</a>';
        }


        return redirect()->to('/#/listbuilder/events');
    }

    /**
     * Logout from SSO and application also
     * @return URL to logout
     */
    public function doLogoutSso() {

        return redirect()->away($this->provider->getLogoutUrl());
    }

    /**
     * 
     * @return URl redirect to SSO myaccout page
     */
    public function getMyAccountUrl() {
        $url = $this->provider->getMyAccountUrl();
        return redirect()->away($url);
    }
    
    /**
     * Do logout in poet after sso logout
     * @return redirect to url
     */ 
    public function doLogout() {
        $command = new  UserLogoutDetails([]);
        $this->run($command,trans('Login::messages.S_Logout'),trans('Login::messages.E_Logout'));
        Auth::logout();
        session()->flush();
        return redirect()->to('login');
    }

}
