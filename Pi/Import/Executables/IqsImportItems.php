<?php

namespace CodePi\Import\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Import\DataSource\ImportItemsDataSource;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs; 
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\Commands\GetItemsList;
use App\Events\ItemActions;
use App\Events\IqsProgress;

/**
 * Handle the execution of import events items
 */
class IqsImportItems implements iCommands {
    /**
     *
     * @var class, instance of ItemsDataSource
     */
   
    private $ImportItemsDataSource;

    /**
     * 
     * @param ItemDs $objItemDs
     * @param ImportItemsDataSource $objImportItemsDataSource
     * @param DataResponse $objDataResponse
     */
    function __construct(ImportItemsDataSource $objImportItemsDataSource) {
             $this->ImportItemsDataSource = $objImportItemsDataSource;
    }

    /**
     * Execution of add item through import
     * 
     * @param object $command
     * @return array of imported items
     */
    function execute($command) {
        $response = $this->ImportItemsDataSource->iqsImportItems($command);

//        if ($response) {
//            broadcast(new ItemActions($response, 'addrow'))->toOthers();
//        }
        return $response;
    }

}
