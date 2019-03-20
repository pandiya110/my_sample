<?php

namespace CodePi\Users\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Login\DataSource\ResetPasswordLink as ResetPasswordLinkDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Base\DataTransformers\DataSourceResponse;
use CodePi\Users\DataTranslators\CollectionFormat;
use CodePi\Users\DataTranslators\UsersTransformer;

class ResetPasswordLink implements iCommands {
	private $dataSource;
	function __construct() {
		$this->dataSource = new ResetPasswordLinkDs ();
		$this->objCollectionFormat = new DataResponse();
	}
	function execute($command) {
		$params = $command->dataToArray ();
		$response=array();
		try{
		if ($params ['password'] == $params ['newPassword']) {
			unset ( $params ['newPassword'],$params ['_token'] );		
			$userDetails = $this->dataSource->saveUserPassword ( $params );
			if($userDetails['status']){
			return new DataSourceResponse($response, 'S_ResetPasswordLink');
		}
		} else {
			
			return new DataSourceResponse([], 'E_ResetPasswordLink',FALSE);
		}
		
		}catch(\Exception $e){
			return new DataSourceResponse($response->getMessage(), 'E_ResetPasswordLink', FALSE);
		}
	}
}
