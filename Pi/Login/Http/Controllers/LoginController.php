<?php

namespace CodePi\Login\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
#use App\Http\Controllers\Controller;
use Auth;
use Redirector;
use Response;
use URL;
#use Session;
#use App\User;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Login\Commands\Login;
use CodePi\Login\Commands\LogOut;
use CodePi\Base\DataTransformers\DataSourceResponse;
#use CodePi\Base\Exceptions\DataValidationException;
#use CodePi\Base\Eloquent\Users;

class LoginController extends PiController {

  
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index() {     
        if(Auth::check()){
            return redirect()->to('/#/listbuilder/events');
        }
        return view('index');
    }

    /**
     * Getting user login
     * @param Request $request
     * @return json
     */
    public function getLogin(Request $request) {
        $data = $request->all();
        $command = new Login($data);
        return $this->run($command, trans('Login::messages.S_Login'), trans('Login::messages.E_Login'));
        
//        $result = CommandFactory::getCommand($command);        
//        $code = ($result['status']) ? 'S_Login' : 'E_Login';
//        $response = new DataSourceResponse([], $code, $result['status']);
//        return \Response::json($response->formatMessage());
        
    }

    /**
     * Getting user logout.
     * @param Request $request
     * @return redirect to url
     */
    public function logout(Request $request) {
        $data = $request->all();
        $url = URL::to('');
        if(isset($data['localLogout']) && $data['localLogout']==true){
               return "You have been logged-out. Click <a href='".$url."'>here </a>to login again.";
        }else{
        $command = new LogOut($data);
        $response = CommandFactory::getCommand($command);
        new DataSourceResponse([], 'S_Logout');
        return redirect('login');
        }
    }
   
    

}
