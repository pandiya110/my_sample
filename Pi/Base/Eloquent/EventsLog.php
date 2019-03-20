<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class EventsLog extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';
    protected $table = 'events_log';
    protected $fillable = array (
                        'id',
			'action',			
			'params',						
			'created_by', 
			'last_modified_by',
			'date_added', 
			'last_modified',
			'gt_date_added',
			'gt_last_modified',
			'ip_address',
                        'reference_id',
                        'changed_fields'
	);

}
