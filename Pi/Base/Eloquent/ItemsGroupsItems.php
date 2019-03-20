<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class ItemsGroupsItems extends Model {

    use PiEloquent;

    protected $table = 'items_groups_items';
    public $timestamps = false;
    protected $fillable = array(
        'id ',
        'items_groups_id',
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
