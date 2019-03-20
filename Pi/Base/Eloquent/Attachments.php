<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;

use CodePi\Base\DataSource\PiEloquent;

class Attachments extends Model { 
    use PiEloquent;

    protected $table = 'attachments';
    public $timestamps = FALSE; 
    protected $fillable = [
        'id', 
        'original_name',
        'db_name',
        'screen_name',  
        'status',
        'resolutions_id',
        'local_to_cloud',
        'local_img_process',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',  
        'gt_date_added',
        'gt_last_modified',
        'ip_address',
        'filesize',
        'box_file_id'
     ];
}
