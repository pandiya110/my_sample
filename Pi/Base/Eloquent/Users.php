<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use CodePi\Base\DataSource\PiEloquent;


class Users extends Model implements AuthenticatableContract, CanResetPasswordContract {

    use Authenticatable,
        CanResetPassword, PiEloquent;

    public $timestamps = false;
    protected $table = 'users';

    //const CREATED_AT = 'create_date';
    //const UPDATED_AT = 'modify_date';
    
    

    protected $fillable = array(
        'id',
        'firstname',
        'lastname',
        'email',
        'password',
        'remember_token',
        'departments_id',
        'status',
        'is_register',
        'activate_exp_time',
        'password_exp_date',
        'profile_id',
        'profile_image_url',
        'created_by',
        'modified_by',
        'created_at',
        'updated_at',
        'ip_address',
        'color_code',
        'roles_id',
        'is_first_login'
    );
    protected $hidden = array(
        'password'
    );
    
    
}
