<?php

/**
 * Copyright 2012-2014 Rackspace US, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace CodePi\Base\Libraries\Upload;


use OpenCloud\Rackspace;

class Cloud {
	
	public $endpoint='https://identity.api.rackspacecloud.com/v2.0/';
	public $userName='Enterpi_';
	public $apiKey='d855d6e68637471b838f3f264d4598e9';
	function __construct($userName=NULL,$apiKey=NULL) {
		
		if(!empty($userName)){
			
			$this->userName=$userName;
		}
		if(!empty($apiKey)){
				
			$this->apiKey=$apiKey;
		}
		
	}
	function rackspace() {
		$endpoint = 'https://identity.api.rackspacecloud.com/v2.0/';
		$credentials = array (
				'username' => $this->userName,
				'apiKey' =>$this->apiKey,
				'curl.options' => array (
						CURLOPT_FORBID_REUSE => true 
				) 
		);
		
		$rackspace = new Rackspace ( $this->endpoint, $credentials );
		// $rackspace->Authenticate();
		
		return $rackspace;
	}
	function uploadObject($source, $destination, $folder) {
		// echo "source--".$source."<br>desti--".$destination."<br>folder--".$folder;
		$client = $this->rackspace ();
		$region = 'DFW';
		$objectStoreService = $client->objectStoreService ( null, $region );
		
		$container = $objectStoreService->getContainer ( $folder );
		// echo $localFileName = public_path(). '/uploads/php_tutorial.pdf';
		// $remoteFileName = 'test1.jpg';
		
		$fileData = fopen ( $source, 'r' );
		$container->uploadObject ( $destination, $fileData );
	}
	function uploadMultipleObjects($folder_name, $objects) {
		$client = $this->rackspace ();
		
		$region = 'DFW';
		$objectStoreService = $client->objectStoreService ( null, $region );
		$container = $objectStoreService->getContainer ( $folder_name );
		
		return $container->uploadObjects ( $objects );
	}
	function downloadObject($folder_name, $objectName,$localFilePath = '') {
		$client = $this->rackspace ();
		$region = 'DFW';
		$objectStoreService = $client->objectStoreService ( null, $region );
		$container = $objectStoreService->getContainer ( $folder_name );
                $object = $container->getObject($objectName);
		$objectContent = $object->getContent();
		$objectContent->rewind ();
		$stream = $objectContent->getStream ();
                if(!empty($localFilePath)){
                    $localFilename = $localFilePath;
                }else{
                    $localFilename = tempnam ( "/tmp", $objectName );    
                }
		file_put_contents ( $localFilename, $stream );
		
		return $localFilename;
	}
	function deleteObject($folder_name, $objectName) {
		$client = $this->rackspace ();
		$region = 'DFW';
		$objectStoreService = $client->objectStoreService ( null, $region );
		$container = $objectStoreService->getContainer ( $folder_name );
		$object = $container->getObject ( $objectName );
		$object->delete ();
	}
        
	function getFileSize($folder_name, $objectName) {
		$client = $this->rackspace ();
		$region = 'DFW';
		$objectStoreService = $client->objectStoreService ( null, $region );
		$container = $objectStoreService->getContainer ( $folder_name );
                $object = $container->getObject ( $objectName );
                return $object->getContentLength();
        }
}
