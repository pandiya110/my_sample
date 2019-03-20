<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class ItemsPriceZones extends Model {

    use PiEloquent;

    protected $table = 'items_price_zones';
    public $timestamps = false;
    protected $fillable = array(
        'id ',
        'items_id',
        'price_zones_id',
        'events_id',
        'master_items_id',
        'is_omit',
        'date_added',
        'created_by',
        'last_modified',
        'last_modified_by',
        'gt_date_added',
        'gt_last_modified',
        'ip_address'
    );

}
