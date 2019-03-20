<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class EventsLiveUsersEdit extends Model {

    use PiEloquent;

    protected $table = 'events_live_users_edit';
    public $timestamps = false;
    protected $fillable = array(
                                'id ',
                                'events_id',
                                'users_id',
                                'items_id',
                                'users_token',
                                'date_added',                                
                                'gt_date_added',                                
                                'ip_address'
                                );

}
