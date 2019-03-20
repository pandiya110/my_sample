<?php

namespace CodePi\Api\Executables;

use CodePi\Api\DataSource\MasterItemsDataSource as MasterItemsDs;

class GetMasterItems  { 
    /**    
     * @access private
     * @var class, this is instance of MasterItemsDs class
     */    
    private $dataSource;
  
    /**
     * Constructor
     * @param MasterItemsDs $objMasterDs     
     */
    public function __construct(MasterItemsDs $objMasterDs) {
        
        $this->dataSource = $objMasterDs;        
        
    }
    /**
     * Execution of get master items 
     * 
     * @param obj $command
     * @return array
     */
    public function execute($command) { 
        $arrResponse = [];        
        $objResult = $this->dataSource->getMasterItemsData($command);
        if(!empty($objResult)){
            $arrResponse = $objResult;
        }
        
        return $arrResponse;
    }
}
