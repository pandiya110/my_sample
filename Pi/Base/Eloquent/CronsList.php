<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model; 
class CronsList extends Model {
    
    use PiEloquent;
    protected $table = 'logs.crons_list';
    public $timestamps = false;
    protected $fillable = array('cron_name', 'cron_code', 'status', 'created_by', 'last_modified_by' , 'date_added' , 'last_modified' , 'gt_date_added' , 'gt_last_modified' , 'ip_address');

    
}
