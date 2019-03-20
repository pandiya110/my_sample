<?php

namespace CodePi\Base\Eloquent;

use CodePi\Base\DataSource\PiEloquent;
use Illuminate\Database\Eloquent\Model;
use Hash, DB, Auth, Config;

class UsersLog extends Model{

	use PiEloquent;
	public $timestamps = false;
    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';
	protected $table = 'users_logs';
	protected $fillable = array (
			'login_time',
			'logout_time',
			'browser',
			'browser_version',
			'user_agent',
			'os',
			'device_type',
			'users_id',
			'ip_address',
			'created_by', 
			'date_added', 
			'last_modified',
			'gt_date_added', 
			'gt_last_modified'
	);
	
	
	
}
