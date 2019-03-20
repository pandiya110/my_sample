<?php

namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class PriceZones extends Model {

    use PiEloquent;

    protected $table = 'price_zones';
    public $timestamps = false;
    protected $fillable = array(
        'id ',
        'short_trait_desc',
        'trait_desc',
        'trait_desc',
        'trait_nbr',
        'versions',
        'type',
        'date_added',
        'created_by',
        'last_modified',
        'last_modified_by',
        'gt_date_added', 
        'gt_last_modified',
        'ip_address'
    );

}
