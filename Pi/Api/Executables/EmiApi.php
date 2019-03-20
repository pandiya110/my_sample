<?php

namespace CodePi\Api\Executables;

use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Api\DataSource\MISDataSource;
use CodePi\Api\DataSource\QaarthDataSource;
use CodePi\Api\DataSource\SFTPDataSource;
use CodePi\Api\DataSource\UBERDataSource;
use CodePi\Api\DataSource\MasterItemsDataSource as MasterItemDs;
use CodePi\Api\DataSource\EmiApiDataSource as EmiApiDataSourceDs;


class EmiApi  { 
    /**
     * @access private 
     * @var class, this is instance of MISDataSource class   
     */        
    
    
    /**
     * @access private
     * @var class, this is instance of MasterItemDs class                     
     */
    private $emiapiDataSource;


    /**
     * Constructor
     * 
     * @param object of MISDataSource $objMisDs
     * @param object of QaarthDataSource $objQarthDs
     * @param object of UBERDataSource $uberObjDs
     * @param object SFTPDataSource $objsftpDs
     */
    public function __construct(EmiApiDataSourceDs $emiapiDataSource) {
       
        $this->emiapiDataSource = $emiapiDataSource;
    }
    
    /**
     * Execution of get the only api columns data from item master
     * 
     * @param object $command
     * @return array
     */
    public function execute($command) { 
      
       $arrResponse = [];
       //$masterData = $this->masterItemDs->getMasterItemsData($command);
       
       /**
        * Get MIS Api columns values
        */
       //$misResult = $this->misDataSource->getApiData($command);
       
       /**
        * Get Qaarth Api columns values
        */
      // $qarthResult = $this->qarthDataSource->getApiData($command);
       /**
        * Get Uber Api columns values
        */
       //$uberResult = $this->uberDataSource->getApiData($command);       
       /**
        * 
        */
       $uberResult = $this->emiapiDataSource->getApiData($command);  
              
       return $arrResponse;
    }
}
