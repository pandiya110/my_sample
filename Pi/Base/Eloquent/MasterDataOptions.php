<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class MasterDataOptions extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';
    protected $table = 'master_data_options';
    protected $fillable = array (
                        'id',
			'name',			
			'module_id',
                        'status',
			'created_by', 
			'last_modified_by',
			'date_added', 
			'last_modified',
			'gt_date_added',
			'gt_last_modified',
			'ip_address'                                                
	);

}
