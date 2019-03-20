<?php

namespace CodePi\Admin\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Admin\DataSource\DepartmentsDataSource as DepartmentsDs; 
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Admin\DataTransformers\DepartmentsDataTransformers as DepartmentsTs;

 /**
 * Handle the execution of Department creation
 */
class AddDepartments implements iCommands { 

    private $dataSource;
    private $objDataResponse;

    /**
     * @ignore It will create an object of Departments 
     */
    function __construct(DepartmentsDs $objDepartmentsDs, DataResponse $objDataResponse) {
        $this->dataSource = $objDepartmentsDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Execution of add or update the department 
     * @param type $command
     * @return array
     */
    function execute($command) {

        $params = $command->dataToArray();
        $objResult = $this->dataSource->saveDepartments($params);
        $response = [];
        /**
         * After update or add , send the updated department information through response
         */
        $command->id = $objResult->id;
        $objResult = $this->dataSource->getDepartments($command);
        $response = $this->objDataResponse->collectionFormat($objResult, new DepartmentsTs(['id', 'name', 'description', 'status']));
        return array_shift($response);
    }

}
