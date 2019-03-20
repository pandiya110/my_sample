<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @ignore It reveals the MasterTableDetails Description
 */
class LogProcess extends Model {

    use PiEloquent;

    protected $table = 'log_process';
    public $timestamps = false;
    protected $fillable = array(
        'id',
        'type',
        'description',
        'attachments_id',
        'date_added'
    );

}
