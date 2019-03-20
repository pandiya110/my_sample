<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;
use Hash,
    DB,
    Auth,
    Config;

class UsersAuthTokens extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'users_auth_tokens';
    protected $fillable = array(
        'id',
        'users_id',
        'token',
        'expire_at'       
    );

}
