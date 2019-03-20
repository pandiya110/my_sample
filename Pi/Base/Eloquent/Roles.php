<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'roles';
    protected $fillable = array(
        'id',
        'name',
        'description',        
        'status',
        'created_by',
        'last_modified_by',
        'date_added',
        'gt_last_modified',     
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address',
    );

}
