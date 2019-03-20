<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class ItemsGroups extends Model {

    use PiEloquent;

    protected $table = 'items_groups';
    public $timestamps = false;
    protected $fillable = array(
        'id ',
        'name',
        'events_id',
        'items_id',
        'created_by',
        'last_modified_by',        
        'date_added' ,
        'last_modified' ,
        'gt_date_added' ,
        'gt_last_modified',
        'ip_address'
    );

}
