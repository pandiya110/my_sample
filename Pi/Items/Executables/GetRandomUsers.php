<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs; 
use CodePi\Base\DataTransformers\DataResponse;


/**
 * Handle the execution of publish items
 */
class GetRandomUsers implements iCommands { 

	private $dataSource;
        private $objDataResponse;
        /**
        * @ignore It will create an object of item datasource
        */
        function __construct(ItemDs $objItemDs, DataResponse $objDataResponse) {
		$this->dataSource = $objItemDs;  
                $this->objDataResponse = $objDataResponse;
	}
        
        /**
        * @param object $command
        * @return array of published items 
        */        
	function execute($command) { 
                		
		$objResult = $this->dataSource->getRandomUsers($command);                
                return $objResult;		
	}
}
