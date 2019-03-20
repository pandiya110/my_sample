<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class ItemsReqVars extends Model {

    use PiEloquent;

    protected $table = 'items_req_vars';
    public $timestamps = false;
    protected $fillable = array(
                                'id ',  
                               'post_var'
                            );
    

}
