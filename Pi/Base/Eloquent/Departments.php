<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class Departments extends Model {

    use PiEloquent;

    protected $table = 'departments';
    public $timestamps = false;
    protected $fillable = array(
        'id ',
        'name',
        //'prefix',
        'description', 
        'created_by' ,
        'last_modified_by' ,
        'date_added' ,
        'last_modified' ,
        'gt_date_added' ,
        'gt_last_modified' ,
        'ip_address' ,
        'status' 
    );

}
