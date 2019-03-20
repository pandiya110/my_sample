<?php


namespace CodePi\ImportExportLog\Commands;

use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Libraries\Agent\BrowserAgent;
use Auth;

class SystemLog extends BaseCommand {
	public $params;
	public $action;
	public $response;
	public $message;
	public $master_id;
	public $master_table;
	public $filename;
	public $id;

	public $process_status;
	public $browser;
	public $user_agent;
	public $csrf_token;
	public $post;
	public $os;
	public $session_id;
	
	function __construct($data) {
		
		parent::__construct(TRUE);
		

		$this->id = (isset($data['id']) && !empty($data['id'])) ? $data['id'] : 0;
		$this->params = PiLib::piIsset($data,'params', ''); //Request Params
		$this->action = PiLib::piIsset($data,'action', ''); //Action i.e import/export
		$this->response = PiLib::piIsset($data,'response', ''); //Response that action returns
		$this->message = PiLib::piIsset($data,'message', ''); //Message
		$this->master_id = PiLib::piIsset($data,'master_id', ''); //Primary key
		$this->master_table = PiLib::piIsset($data,'master_table', ''); //Table on/from which data retrieve/insert
		$this->filename = PiLib::piIsset($data,'filename', ''); //Imported/Exported File Name
		$this->process_status =PiLib::piIsset($data,'process_status', '0');
		if(Auth::check()){
			$objBrowserAgent = new BrowserAgent()  ;
			$browserDet = $objBrowserAgent->getDetails();
		$this->browser = $browserDet['browser'] . " " . $browserDet['browser_version'];
		$this->os = $browserDet['os'];
		$this->user_agent = $browserDet['user_agent'];
		$this->csrf_token = csrf_token();
		$this->session_id = \Session::getId();
		}else{
			$this->browser = '';
			$this->os = '';
			$this->user_agent = '';
			$this->csrf_token = '';
			$this->session_id = '';
		}
		$this->post = $data;
	}
}
