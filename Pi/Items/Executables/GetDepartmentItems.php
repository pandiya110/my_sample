<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Base\DataTransformers\DataResponse;
use CodePi\Items\DataSource\ItemsDataSource;
#use CodePi\Items\DataTransformers\DepartmentItemsDataTransformers as DeptItmTs;

/**
 * 
 */
class GetDepartmentItems  implements iCommands{ 

    private $dataSource;
    private $objDataResponse;

    /**
     * 
     * @param ItemsDataSource $objItemsDataSource
     * @param DataResponse $objDataResponse
     */
    public function __construct(ItemsDataSource $objItemsDataSource, DataResponse $objDataResponse) {
        $this->dataSource = $objItemsDataSource;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * Execution of get items count group by users department
     * 
     * @param object $command
     * @return array $arrResult
     */
    public function execute($command) {
        $arrResult = [];
        $objResult = $this->dataSource->getItemsByDepartments($command);
        $result = $this->dataSource->formatItemsByDepartments($objResult, $command->last_modified_by);
        if(!empty($result)){
            $arrResult['items'] = $result; 
        }
        return $arrResult;
    }

}
