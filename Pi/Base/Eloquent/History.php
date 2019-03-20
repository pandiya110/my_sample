<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @ignore It reveals the MasterTableDetails Description
 */
class History extends Model {

    use PiEloquent;

    protected $table = 'history';
    public $timestamps = false;
    protected $fillable = array(
        'id',
        'table_id',
        'users_id',
        'history',
        'total_history',
        'changed_fields',
        'table_name',
        'action',
        'type',
        'date_added',
        'events_id',
        'date_added',
        'tracking_id'
    );

}
