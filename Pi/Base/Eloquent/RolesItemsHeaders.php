<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class RolesItemsHeaders extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'roles_items_headers';
    protected $fillable = array(
        'id',
        'roles_id',
        'items_headers_id',
        'headers_alias_name',
        'masters_color_id',
        'headers_order_no',
        'status',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address',
    );

}
