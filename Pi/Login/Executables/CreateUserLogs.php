<?php

namespace CodePi\Login\Executables;
use CodePi\Base\Commands\BaseCommand;
use CodePi\Base\Commands\iCommands;
use CodePi\Login\DataSource\LoginDataSource as LoginDS;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Login\DataTransformers\UsersLog as UsersLogTs;

/**
 * @ignore It will handle the master item data and will promote to master item execution
 */
class CreateUserLogs implements iCommands {

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of HierarchyDataSource
     */
    public function __construct(LoginDS $objLoginDS,DataResponse $objDataResponse) {
        $this->dataSource =  $objLoginDS;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * @param object $command
     * @return array It will return hierarchy details
     */
    public function execute(BaseCommand $command) {
        $result = $this->dataSource->loggingDetails($command);
        // $response['data'] = $this->objDataResponse->collectionFormat($result, new UsersLogTs());
        // $response['count'] = $result->total();
        return $result;
    }

}
