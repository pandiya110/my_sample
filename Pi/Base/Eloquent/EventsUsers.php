<?php
namespace CodePi\Base\Eloquent;

use Illuminate\Database\Eloquent\Model;
use CodePi\Base\DataSource\PiEloquent;

class EventsUsers extends Model {
	
	use PiEloquent;
	
	protected $table = 'events_users';
	public $timestamps = false;
	protected $fillable = array(
			'id ',
			'events_id',
			'users_id',
			'created_by' ,
			'last_modified_by' ,
			'date_added' ,
			'last_modified' ,
			'gt_date_added' ,
			'gt_last_modified' ,
			'ip_address'
	);
	
}
