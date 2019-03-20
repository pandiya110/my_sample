<?php

namespace CodePi\Base\Eloquent;
use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class Status extends Model {

    use PiEloquent;

    protected $table = 'statuses';
    public $timestamps = false;
    protected $fillable = array(
        'id ',
        'name',                
        'created_by' ,
        'last_modified_by' ,
        'date_added' ,
        'last_modified' ,
        'gt_date_added' ,
        'gt_last_modified' ,
        'ip_address'         
    );

}
