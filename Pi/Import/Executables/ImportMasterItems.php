<?php

namespace CodePi\Import\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Import\DataSource\ImportMasterItemsDS as ImpMasItmDs;

/**
 * Handle the execution of import events items
 */
class ImportMasterItems implements iCommands {
    /**
     *
     * @var class, instance of ItemsDataSource
     */
    private $dataSource;
    

    /**
     * 
     * @param ItemDs $objItemDs          
     */
    function __construct(ImpMasItmDs $objMasterItemDs) {
        $this->dataSource = $objMasterItemDs;        
    }

    /**
     * Import master items
     * 
     * @param object $command
     * @return array of imported items
     */
    function execute($command) {
        
        $response = [];
        
        $response = $this->dataSource->importMasterItems($command);
       
        return $response;
    }

}
