<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class UsersPassword extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'users_password';
    protected $fillable = array(
        'id',
        'users_id',
        'users_password',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address'
    );

}
