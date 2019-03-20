<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model; 
class EmailTemplates extends Model {
    
    use PiEloquent;
    protected $table = 'email_templates';
    protected $fillable = array('domain', 'name', 'from','from_name','body', 'subject', 'status', 'is_send_unsub_users' , 'date_added' , 'last_modified' , 'gt_date_added' , 'gt_last_modified' , 'ip_address'); 
}
