<?php

namespace CodePi\Import\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Import\DataSource\ImportItemsDataSource;

/**
 * Handle the execution of import events items
 */
class UploadItems implements iCommands {

    /**
     *
     * @var class, instance of ItemsDataSource
     */
    private $dataSource;

    /**
     *      
     * @param ImportItemsDataSource $objImportItemsDataSource     
     */
    function __construct(ImportItemsDataSource $objItemDs) {
        $this->dataSource = $objItemDs;
        
    }

    /**
     * Execution of add item through import
     * 
     * @param object $command
     * @return array of imported items
     */
    function execute($command) {

        $response = [];
        $response = $this->dataSource->uploadItemsFile($command);

        return $response;
    }

}
