<?php

namespace CodePi\Settings\Eloquant;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model; 
class EmailDetails extends Model {
    
    use PiEloquent;
    protected $table = 'logs.email_details';
    protected $fillable = array('to', 'from', 'subject','attachment','page', 'message', 'created_by', 'last_modified_by' , 'date_added' , 'last_modified' , 'gt_date_added' , 'gt_last_modified' , 'ip_address'); 
}
