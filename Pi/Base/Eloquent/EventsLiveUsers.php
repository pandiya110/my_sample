<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class EventsLiveUsers extends Model {

    use PiEloquent;

    protected $table = 'events_live_users';
    public $timestamps = false;
    protected $fillable = array(
                                'id ',
                                'events_id',
                                'users_id',
                                'users_token',
                                'date_added',
                                'last_modified',
                                'gt_date_added',
                                'gt_last_modified',
                                'ip_address'
                                );

}
