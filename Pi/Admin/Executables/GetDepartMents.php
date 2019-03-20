<?php

namespace CodePi\Admin\Executables;

use CodePi\Admin\DataSource\DepartmentsDataSource AS DepartmentsDs;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Admin\DataTransformers\DepartmentsDataTransformers as DepartmentsTs;

/**
 * Handle the execution of get department list
 */
class GetDepartments {

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of Departments
     */
    public function __construct(DepartmentsDs $objDepartmentsDs, DataResponse $objDataResponse) {
        $this->dataSource = $objDepartmentsDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Execution of Get the list of departments
     * @param object $command
     * @return array $response
     */
    public function execute($command) {
        $response = [];
        $objResult = $this->dataSource->getDepartments($command);
        $response['items'] = $this->objDataResponse->collectionFormat($objResult, new DepartmentsTs(['id', 'name', 'description', 'status', /* 'prefix' */]));
        $response['count'] = count($objResult);
        if (!empty($command->page)) {
            $response['total'] = $objResult->total();
            $response['lastpage'] = $objResult->lastPage();
        }
        return $response;
    }

}
