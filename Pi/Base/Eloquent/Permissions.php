<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class Permissions extends Model {

    use PiEloquent;

    protected $table = 'permissions';
    public $timestamps = false;
    protected $fillable = array(
        'id',
        'name',
        'level',
        'code',
        'status',
        'help',
        'parent_id',
        'type',
        'date_added',
    );

}
