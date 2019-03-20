<?php
namespace CodePi\Base\Libraries\Attachments;

use Illuminate\Database\Eloquent\Model;


class Attachments extends Model{
	public $timestamps = false;
	const CREATED_AT = 'date_added';
	const UPDATED_AT = 'last_modified';
	protected $table = 'attachments';
	protected $fillable = array (
			'id',
			'original_name',
			'db_name',
			'screen_name',
			'status',
			'resolutions_id',
			'created_by',
			'last_modified',
			'updated_at',
			'date_added',
			'last_modified_by',
			'gt_date_added',
			'gt_last_modified',
			'ip_address'
				
	);



}