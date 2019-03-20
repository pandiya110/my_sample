<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class UsersTemplates extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'users_templates';
    protected $fillable = array(
                                'id',
                                'name',
                                'users_id',                                
                                'columns',
                                'is_active',
                                'created_by',
                                'last_modified_by',
                                'date_added',
                                'last_modified',
                                'gt_date_added',
                                'gt_last_modified',
                                'ip_address'
                            );

}
