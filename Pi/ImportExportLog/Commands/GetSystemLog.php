<?php


namespace CodePi\ImportExportLog\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;
#use CodePi\Base\Libraries\Agent\BrowserAgent;
#use Auth;

class GetSystemLog extends BaseCommand {
	
	public $action;
	public $master_id;
	public $master_table;
	public $id;
	public $limit;
	public $process_status;
	public $post;
	
	function __construct($data) {
		
		parent::__construct(empty($data['id']));
		

		$this->id = (isset($data['id']) && !empty($data['id'])) ? $data['id'] : 0;
		
		$this->action = PiLib::piIsset($data,'action', ''); //Action i.e import/export
		$this->master_id = PiLib::piIsset($data,'master_id', ''); //Primary key
		$this->master_table = PiLib::piIsset($data,'master_table', ''); //Table on/from which data retrieve/insert
		
		$this->process_status =PiLib::piIsset($data,'process_status', '0');
		$this->limit = PiLib::piIsset($data,'limit', 1);
		$this->post = $data;
	}
}
