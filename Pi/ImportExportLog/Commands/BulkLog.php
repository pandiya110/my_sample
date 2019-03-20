<?php


namespace CodePi\ImportExportLog\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Libraries\Agent\BrowserAgent;
use Auth;

class BulkLog extends BaseCommand {
	public $params;
	public $response;
	public $message;
	
	public $ids;
	public $process_status;
	
	
	function __construct($data) {
		
		parent::__construct(false);
		

		$this->ids = (isset($data['ids']) && !empty($data['ids'])) ? $data['ids'] : 0;//array
		$this->params = PiLib::piIsset($data,'params', ''); //Request Params
		$this->response = PiLib::piIsset($data,'response', ''); //Response that action returns
		$this->message = PiLib::piIsset($data,'message', ''); //Message
		$this->process_status =PiLib::piIsset($data,'process_status', '0');
		
		$this->post = $data;
	}
}
