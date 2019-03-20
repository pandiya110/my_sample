<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;

use CodePi\Base\DataSource\PiEloquent;

class ErrorLogs extends Model { 
    use PiEloquent;

    protected $table = 'error_logs';
    public $timestamps = FALSE; 
    protected $fillable = [
        'id', 
        'message',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',  
        'gt_date_added',
        'gt_last_modified',
        'ip_address',
        'status'   
     ];
}
