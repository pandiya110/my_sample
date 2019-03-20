<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class ItemsSyncLogs extends Model {

    use PiEloquent;

    protected $table = 'items_sync_logs';
    public $timestamps = false;
    protected $fillable = array(
        'id ',
        'items_id',
        'events_id',
        'response',
        'message',
        'process_status',
        'created_by',
        'last_modified_by',        
        'date_added' ,
        'last_modified' ,
        'gt_date_added' ,
        'gt_last_modified',
        'ip_address' ,        
    );

}
