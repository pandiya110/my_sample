<?php

namespace CodePi\Base\Libraries;

use Illuminate\Support\Facades\Auth;
use CodePi\Base\Eloquent\Users;

class PiAuth {


    static function getLoggedUserId($loggedAs=false) {
        if (empty($loggedAs)) {
            if (Auth::check()) {
                return Auth::user()->id;
            }else{
                return 1;// for cron user
            }
        } elseif(!empty(session('laggedAs'))) {
            return session('laggedAs');
            
        }else{
            if (Auth::check()) {
                return Auth::user()->id;
            }else{
                return 1;// for cron user
            }
        }
    }
    
    
    static function getLoggedAsUser() {
        return Users::find(self::getLoggedUserId(true));
    }
}
