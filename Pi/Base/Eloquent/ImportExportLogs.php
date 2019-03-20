<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;

class ImportExportLogs extends Model {

    use PiEloquent;

    public $timestamps = false;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';
    protected $table = 'system_logs';
    protected $fillable = array (
                        'id',
			'action',
			'filename',
			'params',
			'response',
			'message',
			'browser',
			'os',
			'master_id',
			'master_table',
			'process_status',
			'created_by', 
			'last_modified_by',
			'date_added', 
			'last_modified',
			'gt_date_added',
			'gt_last_modified',
			'ip_address'
	);

}
