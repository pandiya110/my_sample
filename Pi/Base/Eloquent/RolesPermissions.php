<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class RolesPermissions extends Model {

    use PiEloquent;

    protected $table = 'roles_permissions';
    public $timestamps = false;
    protected $fillable = array(
        'id',
        'roles_id',
        'permissions_id',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address'
    );

}
