<?php

namespace CodePi\RestApiSync\Executables;

use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Base\DataSource\Elastic;
use CodePi\RestApiSync\DataSource\ItemsDataSource as ItemsDs;
use CodePi\Base\Commands\CommandFactory;

/**
 * Handle the execution of get department list
 */
class SyncItems {

    private $dataSource;

    /**
     * @ignore It will create an object of Departments
     */
    public function __construct(ItemsDs $objItemsDs) {
        $this->dataSource = $objItemsDs;
    }

    /**
     * Execution of Get the list of departments
     * @param object $command
     * @return array $response
     */
    public function execute($command) {
        return $this->dataSource->syncDataToElastic($command);
    }

}
