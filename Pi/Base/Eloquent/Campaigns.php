<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class Campaigns extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';

    protected $table = 'campaigns';
    protected $fillable = array(
        'id',
        'name',
        'description',
        'start_from',
        'end_date',
        'aprimo_campaign_id',
        'aprimo_campaign_type_id',
        'status',
        'type',
        'created_by',
        'last_modified_by',
        'date_added',
        'last_modified',
        'gt_date_added',
        'gt_last_modified',
        'ip_address',
        'assign_status',
        'out_of_market_date'
    );

}
