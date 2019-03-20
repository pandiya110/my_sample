<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class ChannelsEvents extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'channels_events';
    protected $fillable = array(
        'id',
        'events_id',
        'channels_id',
        'created_by',
        'date_added',
        'gt_date_added',
        'ip_address',
    );

}
