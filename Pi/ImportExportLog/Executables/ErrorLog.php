<?php
namespace CodePi\ImportExportLog\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\ImportExportLog\DataSource\ErrorLogDataSource;

class ErrorLog implements iCommands {

	private $dataSource;
	private $objUserTransformer;

	function __construct() {
		$this->dataSource = new ErrorLogDataSource ();
		$this->objDataResponse = new DataResponse ();
	}

	function execute($command) {
		return $this->dataSource->saveLog($command);
	}

}
