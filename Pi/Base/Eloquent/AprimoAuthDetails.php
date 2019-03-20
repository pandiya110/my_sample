<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class AprimoAuthDetails extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'aprimo_auth_details';
    protected $fillable = array(
        'id',
        'aprimo_url',
        'client_id',
        'client_secret',
        'user_token',
        'base64_string',
        'accessToken',
        'refreshToken',       
    );

}
