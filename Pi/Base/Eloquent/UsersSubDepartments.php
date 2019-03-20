<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;


class UsersSubDepartments extends Model {

    use PiEloquent;
    
    protected $table = 'users_sub_departments';
    public $timestamps = false;
    protected $fillable = array(
        'id',
        'users_id',
        'primary_departments_id',
        'sup_departments_id',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address'
    );
    
}
