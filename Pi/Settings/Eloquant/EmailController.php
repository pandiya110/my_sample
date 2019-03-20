<?php

namespace CodePi\Settings\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model; 
class EmailController extends Model {
    
    use PiEloquent;
    protected $table = 'email_controller';
    protected $fillable = array('to', 'from', 'from_name','cc','bcc', 'subject', 'message', 'attachment', 'status', 'sent_date', 'created_by', 'last_modified_by' , 'date_added' , 'last_modified' , 'gt_date_added' , 'gt_last_modified' , 'ip_address');

    
}
