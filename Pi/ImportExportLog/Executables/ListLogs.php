<?php

namespace CodePi\ImportExportLog\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\ImportExportLog\DataSource\SystemLogDataSource as SystemLogDataSourceDs;
use CodePi\Base\DataTransformers\DataResponse;

/**
 * @ignore It will handle the master item data and will promote to master item execution
 */
class ListLogs implements iCommands {

    private $dataSource;

    /**
     * @ignore It will create an object of HierarchyDataSource
     */
    public function __construct(SystemLogDataSourceDs $objSystemLogDataSourceDs,DataResponse $objDataResponse) {
        $this->dataSource =  $objSystemLogDataSourceDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * @param object $command
     * @return array It will return hierarchy details
     */
    public function execute($command) {
        $result = $this->dataSource->getListLogs($command);
        return $result;
    }

}
